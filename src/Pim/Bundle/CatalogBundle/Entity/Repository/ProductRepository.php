<?php

namespace Pim\Bundle\CatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\ProductQueryBuilder;
use Pim\Bundle\CatalogBundle\Model\ProductRepositoryInterface;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Model\ProductValueInterface;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;

/**
 * Product repository
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductRepository extends EntityRepository implements
    ProductRepositoryInterface,
    ReferableEntityRepositoryInterface
{
    /** @param ProductQueryBuilder $productQB */
    protected $productQB;

    /** @param AttributeRepository $attributeRepository */
    protected $attributeRepository;

    /**
     * {@inheritdoc}
     */
    public function buildByScope($scope)
    {
        $qb = $this->findAllByAttributesQB();
        $qb
            ->andWhere(
                $qb->expr()->eq('Entity.enabled', ':enabled')
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('Value.scope', ':scope'),
                    $qb->expr()->isNull('Value.scope')
                )
            )
            ->setParameter('enabled', true)
            ->setParameter('scope', $scope);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function buildByChannelAndCompleteness(Channel $channel)
    {
        $scope = $channel->getCode();
        $qb = $this->buildByScope($scope);
        $rootAlias = $qb->getRootAlias();
        $expression =
            'pCompleteness.product = '.$rootAlias.' AND '.
            $qb->expr()->eq('pCompleteness.ratio', '100').' AND '.
            $qb->expr()->eq('pCompleteness.channel', $channel->getId());

        $qb->innerJoin(
            'Pim\Bundle\CatalogBundle\Model\Completeness',
            'pCompleteness',
            'WITH',
            $expression
        );

        $treeId = $channel->getCategory()->getId();
        $expression = $qb->expr()->eq('pCategory.root', $treeId);
        $qb->innerJoin(
            $rootAlias.'.categories',
            'pCategory',
            'WITH',
            $expression
        );

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function findByExistingFamily()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where(
            $qb->expr()->isNotNull('p.family')
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds(array $ids)
    {
        $qb = $this->findAllByAttributesQB();
        $qb->andWhere(
            $qb->expr()->in('Entity.id', $ids)
        );

        return $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllForVariantGroup(Group $variantGroup, array $criteria = array())
    {
        $qb = $this->createQueryBuilder('Product');

        $qb
            ->where(':variantGroup MEMBER OF Product.groups')
            ->setParameter('variantGroup', $variantGroup);

        $index = 0;
        foreach ($criteria as $item) {
            $code = $item['attribute']->getCode();
            $qb
                ->innerJoin(
                    'Product.values',
                    sprintf('Value_%s', $code),
                    'WITH',
                    sprintf('Value_%s.attribute = ?%d AND Value_%s.option = ?%d', $code, ++$index, $code, ++$index)
                )
                ->setParameter($index - 1, $item['attribute'])
                ->setParameter($index, $item['option']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getFullProduct($id)
    {
        $qb = $this->getFullProductQB();

        return $qb
            ->where('p.id=:id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getFullProducts(array $productIds, array $attributeIds = array())
    {
        $qb = $this->getFullProductQB();
        $qb
            ->addSelect('c, assoc, g')
            ->leftJoin('v.attribute', 'a', $qb->expr()->in('a.id', $attributeIds))
            ->leftJoin('p.categories', 'c')
            ->leftJoin('p.associations', 'assoc')
            ->leftJoin('p.groups', 'g')
            ->where($qb->expr()->in('p.id', $productIds));

        return $qb->getQuery()->execute();
    }

    /**
     * Get full product query builder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getFullProductQB()
    {
        return $this
            ->createQueryBuilder('p')
            ->select('p, f, v, pr, m, o, os')
            ->leftJoin('p.family', 'f')
            ->leftJoin('p.values', 'v')
            ->leftJoin('v.prices', 'pr')
            ->leftJoin('v.media', 'm')
            ->leftJoin('v.option', 'o')
            ->leftJoin('v.options', 'os');
    }

    /**
     * {@inheritdoc}
     */
    public function getProductCountByTree(ProductInterface $product)
    {
        $productMetadata = $this->getClassMetadata(get_class($product));

        $categoryAssoc = $productMetadata->getAssociationMapping('categories');

        $categoryClass = $categoryAssoc['targetEntity'];
        $categoryTable = $this->getEntityManager()->getClassMetadata($categoryClass)->getTableName();

        $categoryAssocTable = $categoryAssoc['joinTable']['name'];

        $sql = "SELECT".
               "    tree.id AS tree_id,".
               "    COUNT(category_product.product_id) AS product_count".
               "  FROM $categoryTable tree".
               "  JOIN $categoryTable category".
               "    ON category.root = tree.id".
               "  LEFT JOIN $categoryAssocTable category_product".
               "    ON category_product.product_id = :productId".
               "   AND category_product.category_id = category.id".
               " GROUP BY tree.id";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('productId', $product->getId());

        $stmt->execute();
        $productCounts = $stmt->fetchAll();
        $trees = array();
        foreach ($productCounts as $productCount) {
            $tree = array();
            $tree['productCount'] = $productCount['product_count'];
            $tree['tree'] = $this->getEntityManager()->getRepository($categoryClass)->find($productCount['tree_id']);
            $trees[] = $tree;
        }

        return $trees;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsCountInCategory(
        CategoryInterface $category,
        QueryBuilder $categoryQb = null
    ) {
        $qb = $this->createQueryBuilder('p');
        $qb->select($qb->expr()->count('distinct p'));
        $qb->join('p.categories', 'node');

        if (null === $categoryQb) {
            $qb->where('node.id = :nodeId');
            $qb->setParameter('nodeId', $category->getId());
        } else {
            $qb->where($categoryQb->getDqlPart('where'));
            $qb->setParameters($categoryQb->getParameters());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductIdsInCategory(
        CategoryInterface $category,
        QueryBuilder $categoryQb = null
    ) {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.id');
        $qb->join('p.categories', 'node');

        if (null === $categoryQb) {
            $qb->where('node.id = :nodeId');
            $qb->setParameter('nodeId', $category->getId());
        } else {
            $qb->where($categoryQb->getDqlPart('where'));
            $qb->setParameters($categoryQb->getParameters());
        }

        $products = $qb->getQuery()->execute(array(), AbstractQuery::HYDRATE_ARRAY);

        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product['id'];
        }
        $productIds = array_unique($productIds);

        return $productIds;
    }

    /**
     * {@inheritdoc}
     */
    public function findByReference($code)
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.values', 'v')
            ->innerJoin('v.attribute', 'a')
            ->where('a.attributeType=:attribute_type')
            ->andWhere('v.varchar=:code')
            ->setParameter('attribute_type', 'pim_catalog_identifier')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceProperties()
    {
        return array($this->getAttributeRepository()->getIdentifierCode());
    }

    /**
     * Replaces name of tables in DBAL queries
     *
     * @param string $sql
     *
     * @return string
     */
    protected function prepareDBALQuery($sql)
    {
        $categoryMapping = $this->getClassMetadata()->getAssociationMapping('categories');

        $valueMapping  = $this->getClassMetadata()->getAssociationMapping('values');
        $valueMetadata = $this->getEntityManager()->getClassMetadata($valueMapping['targetEntity']);

        $attributeMapping  = $valueMetadata->getAssociationMapping('attribute');
        $attributeMetadata = $this->getEntityManager()->getClassMetadata($attributeMapping['targetEntity']);

        return strtr(
            $sql,
            [
                '%category_join_table%' => $categoryMapping['joinTable']['name'],
                '%product_table%'       => $this->getClassMetadata()->getTableName(),
                '%product_value_table%' => $valueMetadata->getTableName(),
                '%attribute_table%'     => $attributeMetadata->getTableName()
            ]
        );
    }

    /**
     * Returns the ProductValue class
     *
     * @return string
     */
    protected function getValuesClass()
    {
        return $this->getClassMetadata()->getAssociationTargetClass('values');
    }

    /**
     * Returns the Attribute class
     *
     * @return string
     */
    protected function getAttributeClass()
    {
        return $this->getEntityManager()
            ->getClassMetadata($this->getValuesClass())
            ->getAssociationTargetClass('attribute');
    }

    /**
     * Returns the Attribute
     *
     * @param string $code
     *
     * @return AbstractAttribute
     */
    protected function getAttributeByCode($code)
    {
        $repository = $this->getEntityManager()->getRepository($this->getAttributeClass());

        return $repository->findOneByCode($code);
    }

    /**
     * @return QueryBuilder
     */
    public function createDatagridQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from($this->_entityName, 'p', 'p.id');

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function createGroupDatagridQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from($this->_entityName, 'p', 'p.id');

        $isCheckedExpr =
            'CASE WHEN ' .
            '(:currentGroup MEMBER OF p.groups '.
            'OR p.id IN (:data_in)) AND p.id NOT IN (:data_not_in)'.
            'THEN true ELSE false END';
        $qb
            ->addSelect($isCheckedExpr.' AS is_checked');

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function createVariantGroupDatagridQueryBuilder()
    {
        $qb = $this->createGroupDatagridQueryBuilder();
        $qb->andWhere($qb->expr()->in('p.id', ':productIds'));

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function createAssociationDatagridQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from($this->_entityName, 'p', 'p.id');

        $qb
            ->leftJoin(
                'Pim\Bundle\CatalogBundle\Model\Association',
                'pa',
                'WITH',
                'pa.associationType = :associationType AND pa.owner = :product AND p MEMBER OF pa.products'
            );

        $qb->andWhere($qb->expr()->neq('p', ':product'));

        $isCheckedExpr =
            'CASE WHEN (pa IS NOT NULL OR p.id IN (:data_in)) AND p.id NOT IN (:data_not_in) ' .
            'THEN true ELSE false END';

        $isAssociatedExpr = 'CASE WHEN pa IS NOT NULL THEN true ELSE false END';

        $qb
            ->addSelect($isCheckedExpr.' AS is_checked')
            ->addSelect($isAssociatedExpr.' AS is_associated');

        return $qb;
    }

    /**
     * Returns true if a ProductValue with the provided value alread exists,
     * false otherwise.
     *
     * @param ProductValueInterface $value
     *
     * @return boolean
     */
    public function valueExists(ProductValueInterface $value)
    {
        $criteria = array(
            'attribute' => $value->getAttribute(),
            $value->getAttribute()->getBackendType() => $value->getData()
        );
        $result = $this->getEntityManager()->getRepository(get_class($value))->findBy($criteria);

        return (
            (0 !== count($result)) &&
            !(1 === count($result) && $value === ($result instanceof \Iterator ? $result->current() : current($result)))
        );
    }

    /**
     * @param integer $variantGroupId
     *
     * @return array product ids
     */
    public function getEligibleProductIdsForVariantGroup($variantGroupId)
    {
        $sql = 'SELECT count(ga.attribute_id) as nb '.
            'FROM pim_catalog_group_attribute as ga '.
            'WHERE ga.group_id = :groupId;';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('groupId', $variantGroupId);
        $stmt->execute();
        $nbAxes = $stmt->fetch()['nb'];

        $sql = 'SELECT v.entity_id '.
            'FROM pim_catalog_group_attribute as ga '.
            "LEFT JOIN %product_value_table% as v ON v.attribute_id = ga.attribute_id ".
            'WHERE ga.group_id = :groupId '.
            'GROUP BY v.entity_id '.
            'having count(v.option_id) = :nbAxes ;';
        $sql = $this->prepareDBALQuery($sql);

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('groupId', $variantGroupId);
        $stmt->bindValue('nbAxes', $nbAxes);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $productIds = array_map(
            function ($row) {
                return $row['entity_id'];
            },
            $results
        );

        return $productIds;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilterByAttribute($qb, AbstractAttribute $attribute, $value, $operator = '=')
    {
        $this->getProductQueryBuilder($qb)->addAttributeFilter($attribute, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilterByField($qb, $field, $value, $operator = '=')
    {
        $this->getProductQueryBuilder($qb)->addFieldFilter($field, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilterByIds($qb, array $productIds, $include)
    {
        $rootAlias  = $qb->getRootAlias();
        if ($include) {
            $expression = $qb->expr()->in($rootAlias .'.id', $productIds);
            $qb->andWhere($expression);

        } else {
            $expression = $qb->expr()->notIn($rootAlias .'.id', $productIds);
            $qb->andWhere($expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applySorterByAttribute($qb, AbstractAttribute $attribute, $direction)
    {
        $this->getProductQueryBuilder($qb)->addAttributeSorter($attribute, $direction);
    }

    /**
     * {@inheritdoc}
     */
    public function applySorterByField($qb, $field, $direction)
    {
        $this->getProductQueryBuilder($qb)->addFieldSorter($field, $direction);
    }

    /**
     * Set flexible query builder
     *
     * @param ProductQueryBuilder $productQB
     *
     * @return ProductRepositoryInterface
     */
    public function setProductQueryBuilder($productQB)
    {
        $this->productQB = $productQB;

        return $this;
    }

    /**
     * Finds entities and attributes values by a set of criteria, same coverage than findBy
     *
     * @param array      $attributes attribute codes
     * @param array      $criteria   criterias
     * @param array|null $orderBy    order by
     * @param int|null   $limit      limit
     * @param int|null   $offset     offset
     *
     * @return array The objects.
     */
    public function findAllByAttributes(
        array $attributes = array(),
        array $criteria = null,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {
        return $this
            ->findAllByAttributesQB($attributes, $criteria, $orderBy, $limit, $offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        $qb = $this->createQueryBuilder('p');
        $pqb = $this->getProductQueryBuilder($qb);
        foreach ($criteria as $field => $data) {
            if (is_array($data)) {
                $pqb->addAttributeFilter($data['attribute'], '=', $data['value']);
            } else {
                $pqb->addFieldFilter($field, '=', $data);
            }
        }

        $result = $qb->getQuery()->execute();

        if (count($result) > 1) {
            throw new \LogicException(
                sprintf(
                    'Many products have been found that match criteria:' . "\n" . '%s',
                    print_r($criteria, true)
                )
            );
        }

        return reset($result);
    }

    /**
     * Load a flexible entity with its attribute values
     *
     * @param integer $id
     *
     * @return Product|null
     * @throws NonUniqueResultException
     */
    public function findOneByWithValues($id)
    {
        $qb = $this->findAllByAttributesQB(array(), array('id' => $id));
        $qb->leftJoin('Attribute.translations', 'AttributeTranslations');
        $qb->leftJoin('Attribute.availableLocales', 'AttributeLocales');
        $qb->addSelect('Value');
        $qb->addSelect('Attribute');
        $qb->addSelect('AttributeTranslations');
        $qb->addSelect('AttributeLocales');
        $qb->leftJoin('Attribute.group', 'AttributeGroup');
        $qb->addSelect('AttributeGroup');
        $qb->leftJoin('AttributeGroup.translations', 'AGroupTranslations');
        $qb->addSelect('AGroupTranslations');

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return ProductQueryBuilder
     */
    protected function getProductQueryBuilder($qb)
    {
        if (!$this->productQB) {
            throw new \LogicException('Product query builder must be configured');
        }

        $this->productQB->setQueryBuilder($qb);

        return $this->productQB;
    }

    /**
     * Add join to values tables
     *
     * @param QueryBuilder $qb
     */
    protected function addJoinToValueTables(QueryBuilder $qb)
    {
        $qb->leftJoin(current($qb->getRootAliases()).'.values', 'Value')
            ->leftJoin('Value.attribute', 'Attribute')
            ->leftJoin('Value.options', 'ValueOption')
            ->leftJoin('ValueOption.optionValues', 'AttributeOptionValue');
    }

    /**
     * Finds entities and attributes values by a set of criteria, same coverage than findBy
     *
     * @param array      $attributes attribute codes
     * @param array      $criteria   criterias
     * @param array|null $orderBy    order by
     * @param int|null   $limit      limit
     * @param int|null   $offset     offset
     *
     * @return array The objects.
     */
    protected function findAllByAttributesQB(
        array $attributes = array(),
        array $criteria = null,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {
        $qb = $this->createQueryBuilder('Entity');
        $this->addJoinToValueTables($qb);

        if (!is_null($criteria)) {
            foreach ($criteria as $attCode => $attValue) {
                $attribute = $this->getAttributeByCode($attCode);
                if ($attribute) {
                    $this->applyFilterByAttribute($qb, $attribute, $attValue);
                } else {
                    $this->applyFilterByField($qb, $attCode, $attValue);
                }
            }
        }
        if (!is_null($orderBy)) {
            foreach ($orderBy as $attCode => $direction) {
                $attribute = $this->getAttributeByCode($attCode);
                if ($attribute) {
                    $this->applySorterByAttribute($qb, $attribute, $direction);
                } else {
                    $this->applySorterByField($qb, $attCode, $direction);
                }
            }
        }

        // use doctrine paginator to avoid count problem with left join of values
        if (!is_null($offset) and !is_null($limit)) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
            $paginator = new Paginator($qb->getQuery());

            return $paginator;
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFromIds(array $ids)
    {
        if (empty($ids)) {
            throw new \LogicException('No products to remove');
        }

        $qb = $this->createQueryBuilder('p');
        $qb
            ->delete($this->_entityName, 'p')
            ->where($qb->expr()->in('p.id', $ids));

        return $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function applyMassActionParameters($qb, $inset, $values)
    {
        $rootAlias = $qb->getRootAlias();
        if ($values) {
            $valueWhereCondition =
                $inset
                ? $qb->expr()->in($rootAlias, $values)
                : $qb->expr()->notIn($rootAlias, $values);
            $qb->andWhere($valueWhereCondition);
        }

        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('from')
            ->select($rootAlias)
            ->from($this->_entityName, $rootAlias);

        // Remove 'entityIds' part from querybuilder (added by flexible pager)
        $whereParts = $qb->getDQLPart('where')->getParts();
        $qb->resetDQLPart('where');

        foreach ($whereParts as $part) {
            if (!is_string($part) || !strpos($part, 'entityIds')) {
                $qb->andWhere($part);
            }
        }

        $qb->setParameters(
            $qb->getParameters()->filter(
                function ($parameter) {
                    return $parameter->getName() !== 'entityIds';
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAttributeIdsToExport(array $productIds)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('a.id')
            ->innerJoin('p.values', 'v')
            ->innerJoin('v.attribute', 'a')
            ->where($qb->expr()->in('p.id', $productIds))
            ->groupBy('a.id');

        $attributes = $qb->getQuery()->getArrayResult();
        $attributeIds = array();
        foreach ($attributes as $attribute) {
            $attributeIds[] = $attribute['id'];
        }

        return $attributeIds;
    }

    /**
     * Set attribute repository
     *
     * @param AttributeRepository $attributeRepository
     *
     * @return ProductRepository
     */
    public function setAttributeRepository(AttributeRepository $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findCommonAttributeIds(array $productIds)
    {
        $attributes = array_merge(
            $this->findFamilyCommonAttributeIds($productIds),
            $this->findValuesCommonAttributeIds($productIds)
        );

        $attributeIds = array();
        foreach ($attributes as $attributeId) {
            $attributeIds[] = (int) $attributeId['id'];
        }

        return $attributeIds;
    }

    /**
     * Find all common attributes ids linked to a family
     * A list of product ids can be passed as parameter
     *
     * @param array $productIds
     *
     * @return array
     */
    protected function findFamilyCommonAttributeIds(array $productIds)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('a.id, COUNT(a.id) AS COUNT_ATT')
            ->innerJoin('p.family', 'f')
            ->innerJoin('f.attributes', 'a')
            ->groupBy('a.id');

        if (!empty($productIds)) {
            $qb->where($qb->expr()->in('p.id', $productIds));

            $subQb = $this->createQueryBuilder('p_sub');
            $subQb
                ->select($subQb->expr()->count('f_sub.id'))
                ->innerJoin('p_sub.family', 'f_sub')
                ->where($subQb->expr()->in('p_sub.id', $productIds));

            $qb->having('COUNT_ATT = ('. $subQb .')');
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find all common attribute ids with values from a list of product ids
     *
     * Can't use ORM here because of QueryBuilder::from method which only take string
     * Only DBAL layer is used
     *
     * @param array $productIds
     *
     * @return array
     */
    protected function findValuesCommonAttributeIds(array $productIds)
    {
        $sql = <<<SQL
    SELECT a.id, COUNT(a.id) AS COUNT_ATT
    FROM (
        SELECT a.id FROM %product_table% p
        INNER JOIN %product_value_table% pv ON pv.entity_id = p.id
        INNER JOIN %attribute_table% a ON a.id = pv.attribute_id
        WHERE p.id IN(%product_ids%)
        GROUP BY p.id, a.id) a
    GROUP BY a.id
    HAVING COUNT_ATT = (
        SELECT COUNT(p.id)
        FROM %product_table% p
        WHERE p.id IN(%product_ids%)
    )
SQL;

        $sql = strtr($sql, ['%product_ids%' => implode($productIds, ',')]);
        $sql = $this->prepareDBALQuery($sql);

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

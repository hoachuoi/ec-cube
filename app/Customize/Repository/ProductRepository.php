<?php

namespace Customize\Repository;

use Eccube\Repository\ProductRepository as BaseProductRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Product;
use Eccube\Repository\QueryKey;
use Eccube\Util\StringUtil;
use Eccube\Entity\ProductStock;

class ProductRepository extends BaseProductRepository
{
    /**
     * @var Queries
     */
    protected $queries;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        RegistryInterface $registry,
        Queries $queries,
        EccubeConfig $eccubeConfig
    ) {
        $this->queries = $queries;
        $this->eccubeConfig = $eccubeConfig;
        parent::__construct($registry,$queries, $eccubeConfig);
    }

    /**
     * get query builder.
     *
     * @param array{
     *         id?:string|int|null,
     *         category_id?:Category,
     *         status?:ProductStatus[],
     *         link_status?:ProductStatus[],
     *         stock_status?:int,
     *         stock?:ProductStock::IN_STOCK|ProductStock::OUT_OF_STOCK,
     *         tag_id?:Tag,
     *         create_datetime_start?:\DateTime,
     *         create_datetime_end?:\DateTime,
     *         create_date_start?:\DateTime,
     *         create_date_end?:\DateTime,
     *         update_datetime_start?:\DateTime,
     *         update_datetime_end?:\DateTime,
     *         update_date_start?:\DateTime,
     *         update_date_end?:\DateTime,
     *         sortkey?:string,
     *         sorttype?:string
     *     } $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData, $isWarehouse = false)
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('pc', 'pi', 'tr', 'ps')
            ->innerJoin('p.ProductClasses', 'pc')
            ->leftJoin('p.ProductImage', 'pi')
            ->leftJoin('pc.TaxRule', 'tr')
            ->leftJoin('pc.ProductStock', 'ps')
            ->andWhere('pc.visible = :visible')
            ->setParameter('visible', true);
        if ($isWarehouse) {
            $qb->andWhere('p.is_warehouse = :isWarehouse')->setParameter('isWarehouse', 1);

        }
        // id
        if (isset($searchData['id']) && StringUtil::isNotBlank($searchData['id'])) {
            $id = preg_match('/^\d{0,10}$/', $searchData['id']) ? $searchData['id'] : null;
            if ($id && $id > '2147483647' && $this->isPostgreSQL()) {
                $id = null;
            }
            $qb
                ->andWhere('p.id = :id OR p.name LIKE :likeid OR pc.code LIKE :likeid')
                ->setParameter('id', $id)
                ->setParameter('likeid', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchData['id']).'%');
        }

        // code
        /*
        if (!empty($searchData['code']) && $searchData['code']) {
            $qb
                ->innerJoin('p.ProductClasses', 'pc')
                ->andWhere('pc.code LIKE :code')
                ->setParameter('code', '%' . $searchData['code'] . '%');
        }

        // name
        if (!empty($searchData['name']) && $searchData['name']) {
            $keywords = preg_split('/[\s　]+/u', $searchData['name'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $qb
                    ->andWhere('p.name LIKE :name')
                    ->setParameter('name', '%' . $keyword . '%');
            }
        }
       */

        // category
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            $Categories = $searchData['category_id']->getSelfAndDescendants();
            if ($Categories) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c')
                    ->andWhere($qb->expr()->in('pct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
            }
        }

        // status
        if (!empty($searchData['status']) && $searchData['status']) {
            $qb
                ->andWhere($qb->expr()->in('p.Status', ':Status'))
                ->setParameter('Status', $searchData['status']);
        }

        // link_status
        if (isset($searchData['link_status']) && !empty($searchData['link_status'])) {
            $qb
                ->andWhere($qb->expr()->in('p.Status', ':Status'))
                ->setParameter('Status', $searchData['link_status']);
        }

        // stock status
        if (isset($searchData['stock_status'])) {
            $qb
                ->andWhere('pc.stock_unlimited = :StockUnlimited AND pc.stock = 0')
                ->setParameter('StockUnlimited', $searchData['stock_status']);
        }

        // stock status
        if (isset($searchData['stock']) && !empty($searchData['stock'])) {
            switch ($searchData['stock']) {
                case [ProductStock::IN_STOCK]:
                    $qb->andWhere('pc.stock_unlimited = true OR pc.stock > 0');
                    break;
                case [ProductStock::OUT_OF_STOCK]:
                    $qb->andWhere('pc.stock_unlimited = false AND pc.stock <= 0');
                    break;
                default:
                    // 共に選択された場合は全権該当するので検索条件に含めない
            }
        }

        // tag
        if (!empty($searchData['tag_id']) && $searchData['tag_id']) {
            $qb
                ->innerJoin('p.ProductTag', 'pt')
                ->andWhere('pt.Tag = :tag_id')
                ->setParameter('tag_id', $searchData['tag_id']);
        }

        // crate_date
        if (!empty($searchData['create_datetime_start']) && $searchData['create_datetime_start']) {
            $date = $searchData['create_datetime_start'];
            $qb
                ->andWhere('p.create_date >= :create_date_start')
                ->setParameter('create_date_start', $date);
        } elseif (!empty($searchData['create_date_start']) && $searchData['create_date_start']) {
            $date = $searchData['create_date_start'];
            $qb
                ->andWhere('p.create_date >= :create_date_start')
                ->setParameter('create_date_start', $date);
        }

        if (!empty($searchData['create_datetime_end']) && $searchData['create_datetime_end']) {
            $date = $searchData['create_datetime_end'];
            $qb
                ->andWhere('p.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        } elseif (!empty($searchData['create_date_end']) && $searchData['create_date_end']) {
            $date = clone $searchData['create_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('p.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_datetime_start']) && $searchData['update_datetime_start']) {
            $date = $searchData['update_datetime_start'];
            $qb
                ->andWhere('p.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        } elseif (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start'];
            $qb
                ->andWhere('p.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }

        if (!empty($searchData['update_datetime_end']) && $searchData['update_datetime_end']) {
            $date = $searchData['update_datetime_end'];
            $qb
                ->andWhere('p.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        } elseif (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('p.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

        // Order By
        if (isset($searchData['sortkey']) && !empty($searchData['sortkey'])) {
            $sortOrder = (isset($searchData['sorttype']) && $searchData['sorttype'] == 'a') ? 'ASC' : 'DESC';

            $qb->orderBy(self::COLUMNS[$searchData['sortkey']], $sortOrder);
            $qb->addOrderBy('p.update_date', 'DESC');
            $qb->addOrderBy('p.id', 'DESC');
        } else {
            $qb->orderBy('p.update_date', 'DESC');
            $qb->addOrderBy('p.id', 'DESC');
        }

        return $this->queries->customize(QueryKey::PRODUCT_SEARCH_ADMIN, $qb, $searchData);
    }
}
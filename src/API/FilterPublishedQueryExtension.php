<?php

namespace App\API;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Comment;
use Doctrine\ORM\QueryBuilder;

class FilterPublishedQueryExtension implements
    QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function applyToCollection(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null)
    {
        $this->apply($qb, $resourceClass);
    }

    private function apply(
        QueryBuilder $qb,
        string $resourceClass
    )
    {
        if (Comment::class === $resourceClass) {
            $qb->andWhere(sprintf("%s.state = 'published'", $qb->getRootAliases()[0]));
        }
    }

    public function applyToItem(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass, array $identifiers,
        string $operationName = null,
        array $context = [])
    {
        $this->apply($qb, $resourceClass);
    }
}
<?php

declare(strict_types=1);

namespace Psi\Bridge\ObjectAgent\Bolt;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Util\ClassUtils;
use Psi\Component\ObjectAgent\AgentInterface;
use Psi\Component\ObjectAgent\Capabilities;
use Psi\Component\ObjectAgent\Exception\BadMethodCallException;
use Psi\Component\ObjectAgent\Exception\ObjectNotFoundException;
use Psi\Component\ObjectAgent\Query\Comparison;
use Psi\Component\ObjectAgent\Query\Query;
use Bolt\Storage\EntityManager;
use Psi\Bridge\ObjectAgent\Bolt\ExpressionVisitor;
use Doctrine\DBAL\Query\QueryBuilder;

class BoltAgent implements AgentInterface
{
    const SOURCE_ALIAS = 'a';

    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): Capabilities
    {
        return Capabilities::create([
            'can_set_parent' => false,
            'can_query_count' => true,
            'supported_comparators' => [
                Comparison::EQUALS,
                Comparison::NOT_EQUALS,
                Comparison::LESS_THAN,
                Comparison::LESS_THAN_EQUAL,
                Comparison::GREATER_THAN,
                Comparison::GREATER_THAN_EQUAL,
                Comparison::IN,
                Comparison::NOT_IN,
                Comparison::CONTAINS,
                Comparison::NOT_CONTAINS,
                Comparison::NULL,
                Comparison::NOT_NULL,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function find($identifier, string $class = null)
    {
        if (null === $class) {
            throw BadMethodCallException::classArgumentIsMandatory(__CLASS__);
        }

        $object = $this->entityManager->find($class, $identifier);

        // bolt returns "false" if not found ...
        if (!$object) {
            throw ObjectNotFoundException::forClassAndIdentifier($class, $identifier);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function findMany(array $identifiers, string $class = null)
    {
        if (null === $class) {
            throw BadMethodCallException::classArgumentIsMandatory(__CLASS__);
        }

        $idField = 'id';

        $repository = $this->entityManager->getRepository($class);
        $queryBuilder = $repository->createQueryBuilder('a');
        $queryBuilder->where($queryBuilder->expr()->in('a.' . $idField, $identifiers));

        return $repository->findWith($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        $this->entityManager->save($object);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        $this->entityManager->delete($object);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        // bolt does not support flush
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($object)
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function setParent($object, $parent)
    {
        throw new \BadMethodCallException(
            'Doctrine ORM is not a hierarhical storage system, cannot set parent.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $class): bool
    {
        $mapper = $this->entityManager->getMapper();

        return (bool) $mapper->getClassMetadata($class);
    }

    /**
     * {@inheritdoc}
     */
    public function query(Query $query): \Traversable
    {
        $queryBuilder = $this->getQueryBuilder($query);

        foreach ($query->getOrderings() as $field => $order) {
            $queryBuilder->addOrderBy(self::SOURCE_ALIAS . '.' . $field, $order);
        }

        if (null !== $query->getFirstResult()) {
            $queryBuilder->setFirstResult($query->getFirstResult());
        }

        if (null !== $query->getMaxResults()) {
            $queryBuilder->setMaxResults($query->getMaxResults());
        }

        $repository = $this->entityManager->getRepository($query->getClassFqn());

        return new ArrayCollection(
            $repository->findWith($queryBuilder)
        );
    }

    public function queryCount(Query $query): int
    {
        $queryBuilder = $this->getQueryBuilder($query);
        $queryBuilder->select('count(' . self::SOURCE_ALIAS . '.id)');
        $statement = $queryBuilder->execute();
        return (int) $statement->fetchColumn();
    }

    /**
     * Return the entity manager instance (for use in events).
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    private function getQueryBuilder(Query $query): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository($query->getClassFqn())->createQueryBuilder(self::SOURCE_ALIAS);
        $visitor = new ExpressionVisitor(
            $queryBuilder->expr(),
            self::SOURCE_ALIAS
        );

        if ($query->hasExpression()) {
            $expr = $visitor->dispatch($query->getExpression());
            $queryBuilder->where($expr);
            $queryBuilder->setParameters($visitor->getParameters());
        }

        return $queryBuilder;
    }
}

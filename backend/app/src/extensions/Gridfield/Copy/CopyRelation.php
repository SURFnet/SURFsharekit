<?php

namespace SurfSharekit\extensions\Gridfield\Copy;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

class CopyRelation
{

    private $relationClass;
    private $relation;
    private $foreignKey;
    private $relations = [];
    private $callback = null;
    private $recursive = false;
    private $recursiveRelation;
    private $recursiveForeignKey;

    public function __construct(string $relationClass, string $relation, string $foreignKey, array $relations = [], \Closure $callback = null) {
        $this->relationClass = $relationClass;
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;
        $this->relations = $relations;
        $this->callback = $callback;
    }

    public function doCopyRelation(DataObject $original, DataObject $clone) {
        $relationItems = $this->getOriginalRelationItems($original, $this->getRelation());

        if (0 >= $count = $relationItems->count()) {
            return;
        }

        if (!($relationItems instanceof HasManyList)) {
            throw new \Exception(get_class($relationItems) . ' relation not supported');
        }

        foreach ($relationItems as $relationItem) {
            /** @var DataObject $relationItem */
            $relationItemClone = $relationItem->duplicate(false);

            if ($this->getCallback()) {
                $callback = $this->callback;
                $callback($relationItemClone);
            }

            $this->setParentId($clone, $relationItemClone);

            if ($this->isRecursive()) {
                $relationAlreadySet = false;
                foreach ($this->relations as $searchRelation) {
                    if ($searchRelation->relation == $this->getRecursiveRelation()) {
                        $relationAlreadySet = true;
                    }
                }

                if ($relationAlreadySet == false) {
                    array_unshift(
                        $this->relations,
                        (new CopyRelation($this->getRelationClass(), $this->getRecursiveRelation(), $this->getRecursiveForeignKey(), $this->getRelations(), $this->getCallback()))
                            ->recursive($this->getRecursiveRelation(), $this->getRecursiveForeignKey())
                    );
                }
            }

            $relationItemClone->write();

            if (count($this->relations) > 0) {
                foreach ($this->relations as $relation) {
                    /** @var CopyRelation $relation */
                    $relation->doCopyRelation($relationItem, $relationItemClone);
                }
            }

        }
    }

    private function getOriginalRelationItems(DataObject $original, string $relation) {
        return $original->{$relation}();
    }

    private function setParentId(DataObject $parent, DataObject $item) {
        $item->{$this->getForeignKey()} = $parent->ID;
    }


    /**
     * @return string
     */
    public function getRelationClass(): string {
        return $this->relationClass;
    }

    /**
     * @param string $relationClass
     */
    public function setRelationClass(string $relationClass): void {
        $this->relationClass = $relationClass;
    }

    /**
     * @return string
     */
    public function getForeignKey(): string {
        return $this->foreignKey;
    }

    /**
     * @param string $foreignKey
     */
    public function setForeignKey(string $foreignKey): void {
        $this->foreignKey = $foreignKey;
    }

    /**
     * @return array
     */
    public function getRelations(): array {
        return $this->relations;
    }

    /**
     * @param array $relations
     */
    public function setRelations(array $relations): void {
        $this->relations = $relations;
    }

    /**
     * @return string
     */
    public function getRelation(): string {
        return $this->relation;
    }

    /**
     * @param string $relation
     */
    public function setRelation(string $relation): void {
        $this->relation = $relation;
    }

    /**
     * @return null
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * @param null $callback
     */
    public function setCallback($callback): void {
        $this->callback = $callback;
    }

    public function recursive(string $relation, string $foreignKey): self {
        $this->recursive = true;
        $this->recursiveRelation = $relation;
        $this->recursiveForeignKey = $foreignKey;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRecursive(): bool {
        return $this->recursive;
    }

    /**
     * @param bool $recursive
     */
    public function setRecursive(bool $recursive): void {
        $this->recursive = $recursive;
    }

    /**
     * @return mixed
     */
    public function getRecursiveRelation() {
        return $this->recursiveRelation;
    }

    /**
     * @param mixed $recursiveRelation
     */
    public function setRecursiveRelation($recursiveRelation): void {
        $this->recursiveRelation = $recursiveRelation;
    }

    /**
     * @return mixed
     */
    public function getRecursiveForeignKey() {
        return $this->recursiveForeignKey;
    }

    /**
     * @param mixed $recursiveForeignKey
     */
    public function setRecursiveForeignKey($recursiveForeignKey): void {
        $this->recursiveForeignKey = $recursiveForeignKey;
    }

}
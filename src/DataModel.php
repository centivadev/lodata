<?php

namespace Flat3\OData;

class DataModel
{
    /** @var ObjectArray $resources */
    protected $resources;

    /** @var ObjectArray $entityTypes */
    protected $entityTypes;

    public function __construct()
    {
        $this->resources = new ObjectArray();
        $this->entityTypes = new ObjectArray();
    }

    public function entityType(EntityType $entityType): self
    {
        $entityType->setDataModel($this);
        $this->entityTypes[] = $entityType;

        return $this;
    }

    public function resource(Resource $resource): self
    {
        $this->resources[] = $resource;
        return $this;
    }

    public function getNamespace(): string
    {
        return config('odata.namespace') ?: 'com.example.odata';
    }

    public function getEntityTypes(): ObjectArray
    {
        return $this->entityTypes;
    }

    public function getResources(): ObjectArray
    {
        return $this->resources;
    }
}
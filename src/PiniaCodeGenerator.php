<?php

namespace Dev1437\PiniaModelGenerator;

use Dev1437\ModelParser\ModelParser;
use Illuminate\Support\Str;

class PiniaCodeGenerator
{
    private $piniaImports = [];
    private $modelImports = [];
    private $enumCode = '';
    public $morphRelationships = [];

    public $pivotRelations = [];

    private $mappings = [
        'bigint' => 'number',
        'int' => 'number',
        'integer' => 'number',
        'text' => 'string',
        'string' => 'string',
        'decimal' => 'number',
        'datetime' => 'Date',
        'date' => 'Date',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'json' => '[]',
        'array' => 'string[]',
        'point' => 'Point',
    ];

    private $piniaMappings = [
        'number' => 'Num',
        'string' => 'Str',
        'boolean' => 'Bool',
    ];

    private $piniaDefaults = [
        'number' => '(0)',
        'string' => '(\'\')',
        'boolean' => '(false)',
        'number?' => '(0, { nullable: true })',
        'string?' => '(\'\', { nullable: true })',
        'boolean?' => '(false, { nullable: true })',
    ];

    private $piniaNullableDefaults = [
        'number' => '(0, { nullable: true })',
        'string' => '(\'\', { nullable: true })',
        'boolean' => '(false, { nullable: true })',
    ];

    private $piniaRelations = [
        'HasOne' => 'HasOne',
        'HasMany' => 'HasMany',
        'BelongsTo' => 'BelongsTo',
        'BelongsToMany' => 'BelongsToMany',
        'MorphOne' => 'MorphOne',
        'MorphMany' => 'MorphMany',
        'MorphToMany' => 'BelongsToMany',
        'MorphTo' => 'MorphTo',
    ];

    private $parser = null;

    public function __construct($model, $ignoreHidden = false, $filter = [])
    {
        $this->parser = new ModelParser($model, $ignoreHidden, $filter);
    }

    public function generateCodeForModel($oldContent = '')
    {
        $details = $this->parser->parse();

        $modelName = explode('\\', $details['model']);
        $modelName = $modelName[array_key_last($modelName)];

        $entityName = Str::lower($modelName).'s';

        $code = "export default class $modelName extends Model {\n";
        $code .= "  static entity = 'orm/$entityName'\n";
        if (count($details['fields']) > 0) {
            $code .= "  // fields\n";
            foreach ($details['fields'] as $key => $value) {
                $mappedType = array_key_exists($value['type'], $this->mappings) ? $this->mappings[$value['type']] : 'string';
                $piniaAttribute = 'Attr';
                if (array_key_exists($mappedType, $this->piniaMappings)) {
                    $piniaAttribute = $this->piniaMappings[$mappedType];
                }
                $this->piniaImports[] = $piniaAttribute;

                if (array_key_exists($key, $details['casts']) && $details['casts'][$key]['casted_as'] === 'enum') {
                    $castedField = $details['casts'][$key];
                    $code .= $this->createPiniaEnumAttribute($piniaAttribute, $key, $castedField, $value, $mappedType);
                } else {
                    $code .= $this->createPiniaAttribute($piniaAttribute, $key, $value, $mappedType);
                }
            }
        }
        if (count($details['mutators']) > 0) {
            $code .= "  // mutators\n";
            foreach ($details['mutators'] as $key => $value) {
                $mappedType = array_key_exists($value['type'], $this->mappings) ? $this->mappings[$value['type']] : 'string';
                $piniaAttribute = 'Attr';
                if (array_key_exists($mappedType, $this->piniaMappings)) {
                    $piniaAttribute = $this->piniaMappings[$mappedType];
                }
                $this->piniaImports[] = $piniaAttribute;

                if (array_key_exists('enum', $value)) {
                    $code .= $this->createPiniaEnumMutator($piniaAttribute, $key, $value, $mappedType);
                } else {
                    $code .= $this->createPiniaAttribute($piniaAttribute, $key, $value, $mappedType);
                }
            }
        }
        if (count($details['relations']) > 0) {
            $code .= "  // relations\n";
            foreach ($details['relations'] as $key => $value) {
                if (!array_key_exists($value['type'], $this->piniaRelations)) {
                    continue;
                }

                $mappedRelation = $this->piniaRelations[$value['type']];
                $this->piniaImports[] = $mappedRelation;

                $code .= $this->createPiniaRelation($modelName, $key, $value, $mappedRelation);
            }
        }

        $code .= '  /* --- user code --- */'.PHP_EOL;

        if (preg_match('/[\s]*\/\* --- user code --- \*\/\n([\s\S]*?)\n[\s]*\/\* --- end user code --- \*\//', $oldContent, $userHeader)) {
            $code .= $userHeader[1].PHP_EOL;
        }

        $code .= '  /* --- end user code --- */'.PHP_EOL;
        $code .= "}\n";

        $piniaHeader = "import { Model } from 'pinia-orm';\n";

        $piniaHeader .= 'import { ';
        foreach (array_unique($this->piniaImports) as $import) {
            $piniaHeader .= "$import,";
        }
        $piniaHeader .= " } from 'pinia-orm/dist/decorators';\n";

        foreach ($this->modelImports as $model) {
            $piniaHeader .= "import $model from './$model';\n";
        }

        foreach ($this->pivotRelations as $relation) {
            $camelCasePivot = str_replace('_', '', ucwords($relation['pivot']['table'], '_'));
            $piniaHeader .= "import $camelCasePivot from './$camelCasePivot';\n";
        }

        $piniaHeader .= '/* --- user header --- */'.PHP_EOL;

        if (preg_match('/\/\* --- user header --- \*\/\n([\s\S]*?)\n\/\* --- end user header --- \*\//', $oldContent, $userHeader)) {
            $piniaHeader .= $userHeader[1].PHP_EOL;
        }
        $piniaHeader .= '/* --- end user header --- */'.PHP_EOL;
        $code = "$piniaHeader\n$this->enumCode$code";

        return $code;
    }

    private function createPiniaRelation($modelName, $relation, $value, $mappedRelation)
    {
        $piniaType = $value['model'];

        $piniaType .= substr($relation, -1) === 's' ? '[]' : '';

        $lowercaseName = Str::lower($modelName);
        $relationshipDefault = "(() => {$value['model']}, '{$lowercaseName}_id')";

        if ($mappedRelation === 'HasOne' || $mappedRelation === 'HasMany') {
            $this->modelImports[] = $value['model'];
            $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['local_key']}')";
        } elseif ($mappedRelation === 'MorphOne') {
            if (!array_key_exists($value['model'], $this->morphRelationships)) {
                $this->morphRelationships[$value['model']] = [];
            }

            $relationName = explode('_', $value['keys']['morph_type'])[0];

            if (!array_key_exists($relationName, $this->morphRelationships[$value['model']])) {
                $this->morphRelationships[$value['model']][$relationName] = [];
            }
            $this->modelImports[] = $value['model'];
            $this->morphRelationships[$value['model']][$relationName][] = $modelName;
            $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}', '{$value['keys']['local_key']}')";
        } elseif ($mappedRelation === 'BelongsToMany') {
            $camelCasePivot = str_replace('_', '', ucwords($value['pivot']['table'], '_'));
            $this->pivotRelations[$value['pivot']['table']] = $value;
            $this->modelImports[] = $value['model'];
            $relationshipDefault = "(() => {$value['model']}, () => $camelCasePivot, '{$value['keys']['pivot_foreign_key']}', '{$value['keys']['pivot_related_key']}', '{$value['keys']['parent_key']}', '{$value['keys']['related_key']}')";
        } elseif ($mappedRelation === 'BelongsTo') {
            $this->modelImports[] = $value['model'];
            $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['owner_key']}')";
        } elseif ($mappedRelation === 'MorphTo') {
            // Add marker to be replaced by morph backfill later on
            $piniaType = "<MORPHTYPE:$modelName:$relation>";
            $relationshipDefault = "(() => <MORPH:$modelName:$relation>, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}')";
        } elseif ($mappedRelation === 'MorphMany') {
            if (!array_key_exists($value['model'], $this->morphRelationships)) {
                $this->morphRelationships[$value['model']] = [];
            }

            $relationName = explode('_', $value['keys']['morph_type'])[0];

            if (!array_key_exists($relationName, $this->morphRelationships[$value['model']])) {
                $this->morphRelationships[$value['model']][$relationName] = [];
            }

            $this->morphRelationships[$value['model']][$relationName][] = $modelName;

            $this->modelImports[] = $value['model'];
            $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}', '{$value['keys']['local_key']}')";
        }

        return "  @$mappedRelation$relationshipDefault $relation!: $piniaType\n";
    }

    public function createPiniaAttribute($piniaAttribute, $attribute, $value, $mappedType)
    {
        $piniaDefault = '(null)';

        $attributeHasPiniaType = array_key_exists($mappedType, $this->piniaMappings);
        if ($attributeHasPiniaType) {
            $piniaDefault = $this->piniaDefaults[$mappedType];
            if ($value['nullable']) {
                $piniaDefault = $this->piniaNullableDefaults[$mappedType];
            }
        }

        $nullable = $value['nullable'] ? ' | null' : '';

        return "  @$piniaAttribute$piniaDefault $attribute!: $mappedType$nullable\n";
    }

    public function createPiniaEnumMutator($piniaAttribute, $attribute, $value, $mappedType)
    {
        $piniaDefault = '(null)';

        $mappedType = explode('\\', $value['type']);
        $mappedType = $mappedType[array_key_last($mappedType)];

        $this->enumCode .= "export enum $mappedType {\n";
        foreach ($value['enum'] as $enumKey => $enumValue) {
            $this->enumCode .= "  $enumKey = $enumValue,\n";
        }
        $this->enumCode .= "};\n\n";

        $nullable = $value['nullable'] ? ' | null' : '';

        return "  @$piniaAttribute$piniaDefault $attribute!: $mappedType$nullable\n";
    }

    public function createPiniaEnumAttribute($piniaAttribute, $attribute, $castedField, $value, $mappedType)
    {
        $piniaDefault = '(null)';

        $mappedType = explode('\\', $castedField['type']);
        $mappedType = $mappedType[array_key_last($mappedType)];

        $this->enumCode .= "export enum $mappedType {\n";
        foreach ($castedField['values'] as $enumKey => $enumValue) {
            $this->enumCode .= "  $enumKey = $enumValue,\n";
        }
        $this->enumCode .= "};\n\n";

        $nullable = $value['nullable'] ? ' | null' : '';

        return "  @$piniaAttribute$piniaDefault $attribute!: $mappedType$nullable\n";
    }
}

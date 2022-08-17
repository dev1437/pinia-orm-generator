<?php

namespace Dev1437\PiniaModelGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PiniaCodeGenerator;
use ReflectionClass;

class PiniaModelsBuilder
{
    public $piniaImports = [];
    public $modelImports = [];
    public $morphRelationships = [];
    public $enumCode = '';

    public $pivotRelations = [];

    public function buildModels()
    {
        if (!File::isDirectory(resource_path('js/models'))) {
            File::makeDirectory(resource_path('js/models'));
        }

        $pivotRelations = [];
        $morphRelationships = [];

        $models = $this->getModels();

        $modelParams = [
            User::class => [
                'filters' => [
                    'email_verified_at',
                ],
            ],
        ];

        foreach ($models as $model) {
            $modelName = explode('\\', $model);
            $modelName = $modelName[array_key_last($modelName)];

            $pcg = new PiniaCodeGenerator($model, true, array_key_exists($model, $modelParams) ? $modelParams[$model]['filters'] : []);

            $file = resource_path("js/models/$modelName.ts");

            $oldContents = '';

            if (File::exists($file)) {
                $oldContents = File::get($file);
            }

            File::put(
                resource_path("js/models/$modelName.ts"),
                $pcg->generateCodeForModel($oldContents)
            );

            $pivotRelations = array_merge(
                $pivotRelations,
                $pcg->pivotRelations
            );

            $morphRelationships = array_merge_recursive(
                $morphRelationships,
                $pcg->morphRelationships
            );
        }

        $this->generatePivotTable($pivotRelations);

        $this->replaceMorphTypes($morphRelationships);
    }

    /**
     * Get a list of all models.
     *
     * @return Collection
     */
    private function getModels()
    {
        $models = collect(File::allFiles(app_path('Models')))
            ->map(function ($item) {
                $model = substr($item->getFilename(), 0, -4);

                return "App\\Models\\{$model}";
            })->filter(function ($class) {
                $valid = false;
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) && !$reflection->isAbstract();
                }

                return $valid;
            });

        return $models->values();
    }

    public function replaceMorphTypes($morphRelationships)
    {
        collect(File::allFiles(resource_path('js/models')))->filter(function ($item) use ($morphRelationships) {
            $model = substr($item->getFilename(), 0, -3);

            return array_key_exists($model, $morphRelationships);
        })->map(function ($item) use ($morphRelationships) {
            $model = substr($item->getFilename(), 0, -3);

            foreach ($morphRelationships[$model] as $relationship => $models) {
                $morphModels = '[';
                $morphType = '';
                $modelImports = [];

                foreach ($models as $morphModel) {
                    $morphModels .= "$morphModel, ";
                    $morphType .= "{$morphModel}[] | ";
                    $modelImports[] = "import $morphModel from './$morphModel';";
                }

                $morphType = substr($morphType, 0, -3);
                $morphModels = substr($morphModels, 0, -2);

                $morphModels .= ']';
                $morphType .= '';

                $result = preg_replace("/\<MORPH:$model:$relationship\>/m", $morphModels, $item->getContents());

                $result = preg_replace("/\<MORPHTYPE:$model:$relationship\>/m", $morphType, $result);

                $splitFile = explode(PHP_EOL, $result);

                array_splice($splitFile, 1, 0, $modelImports);

                $result = implode(PHP_EOL, $splitFile);

                File::put($item->getPathname(), $result);
            }
        });
    }

    private function generatePivotTable($pivotRelations)
    {
        $mappings = [
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

        $piniaMappings = [
            'number' => 'Num',
            'string' => 'Str',
            'boolean' => 'Bool',
        ];

        $piniaDefaults = [
            'number' => '(0)',
            'string' => '(\'\')',
            'boolean' => '(false)',
            'number?' => '(0, { nullable: true })',
            'string?' => '(\'\', { nullable: true })',
            'boolean?' => '(false, { nullable: true })',
        ];

        $piniaNullableDefaults = [
            'number' => '(0, { nullable: true })',
            'string' => '(\'\', { nullable: true })',
            'boolean' => '(false, { nullable: true })',
        ];

        foreach ($pivotRelations as $relation) {
            $code = '';

            $camelCaseRelation = Str::ucfirst(Str::camel($relation['pivot']['table']));

            $code .= "export default class $camelCaseRelation extends Model {\n";
            $code .= "  static primaryKey = ['{$relation['keys']['pivot_foreign_key']}', '{$relation['keys']['pivot_related_key']}'];\n";
            $code .= "  static entity = '$camelCaseRelation'\n";

            $piniaImports = [];
            $enumCode = '';
            foreach ($relation['pivot']['columns'] as $column => $value) {
                $mappedType = array_key_exists($value['type'], $mappings) ? $mappings[$value['type']] : 'string';

                $piniaAttribute = 'Attr';
                $piniaDefault = '(null)';
                if (array_key_exists('enum', $value)) {
                    preg_match('/.*\\\\(.*)/', $value['type'], $matches);

                    $enumCode .= "export enum {$matches[1]} {\n";
                    foreach ($value['enum'] as $enumKey => $enumValue) {
                        $enumCode .= "  $enumKey = $enumValue,\n";
                    }
                    $enumCode .= "};\n\n";
                    $mappedType = $matches[1];
                } elseif (array_key_exists($mappedType, $piniaMappings)) {
                    $piniaAttribute = $piniaMappings[$mappedType];
                    $piniaDefault = $piniaDefaults[$mappedType];
                    if ($value['nullable']) {
                        $piniaDefault = $piniaNullableDefaults[$mappedType];
                    }
                }

                $piniaImports[] = $piniaAttribute;
                $nullable = $value['nullable'] ? ' | null' : '';

                $code .= "  @$piniaAttribute$piniaDefault $column!: $mappedType$nullable\n";
            }

            $piniaImports = array_unique($piniaImports);
            $piniaHeader = 'import { Model';
            foreach ($piniaImports as $import) {
                $piniaHeader .= ", $import";
            }
            $piniaHeader .= " } from 'pinia-orm';\n\n";

            $code .= '}';

            File::put(resource_path("js/models/$camelCaseRelation.ts"), $piniaHeader.$code);
        }
    }

    public function createPiniaAttribute($attribute, $value)
    {
        $mappedType = array_key_exists($value['type'], $this->mappings) ? $this->mappings[$value['type']] : 'string';

        $piniaAttribute = 'Attr';
        $piniaDefault = '(null)';
        if (array_key_exists('enum', $value)) {
            preg_match('/.*\\\\(.*)/', $value['type'], $matches);

            $this->enumCode .= "export enum {$matches[1]} {\n";
            foreach ($value['enum'] as $enumKey => $enumValue) {
                $this->enumCode .= "  $enumKey = $enumValue,\n";
            }
            $this->enumCode .= "};\n\n";
            $mappedType = $matches[1];
        } elseif (array_key_exists($mappedType, $this->piniaMappings)) {
            $piniaAttribute = $this->piniaMappings[$mappedType];
            $piniaDefault = $this->piniaDefaults[$mappedType];
            if ($value['nullable']) {
                $piniaDefault = $this->piniaNullableDefaults[$mappedType];
            }
        }

        $this->piniaImports[] = $piniaAttribute;
        $nullable = $value['nullable'] ? ' | null' : '';

        return "  @$piniaAttribute$piniaDefault $attribute!: $mappedType$nullable\n";
    }
}

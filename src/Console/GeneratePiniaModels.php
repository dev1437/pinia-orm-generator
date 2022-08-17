<?php

namespace Dev1437\PiniaModelGenerator\Console;

use Dev1437\PiniaModelGenerator\PiniaModelsBuilder;
use Illuminate\Console\Command;

class GeneratePiniaModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piniamodels:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Pinia ORM models from your laravel models';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pmb = new PiniaModelsBuilder();

        $pmb->buildModels();

        return Command::SUCCESS;
    }

    // private function replaceMorphTypes()
    // {
    //     collect(File::allFiles(resource_path('js/models')))->filter(function ($item) {
    //         $model = substr($item->getFilename(), 0, -3);

    //         return array_key_exists($model, $this->morphRelationships);
    //     })->map(function ($item) {
    //         $model = substr($item->getFilename(), 0, -3);

    //         foreach ($this->morphRelationships[$model] as $relationship => $models) {
    //             $morphModels = '[';
    //             $morphType = '';
    //             $modelImports = [];

    //             foreach ($models as $morphModel) {
    //                 $morphModels .= "$morphModel, ";
    //                 $morphType .= "{$morphModel}[] | ";
    //                 $modelImports[] = "import $morphModel from './$morphModel';";
    //             }

    //             $morphType = substr($morphType, 0, -3);
    //             $morphModels = substr($morphModels, 0, -2);

    //             $morphModels .= ']';
    //             $morphType .= '';

    //             $result = preg_replace("/\<MORPH:$model:$relationship\>/m", $morphModels, $item->getContents());

    //             $result = preg_replace("/\<MORPHTYPE:$model:$relationship\>/m", $morphType, $result);

    //             $splitFile = explode(PHP_EOL, $result);

    //             array_splice($splitFile, 1, 0, $modelImports);

    //             $result = implode(PHP_EOL, $splitFile);

    //             File::put($item->getPathname(), $result);
    //         }
    //     });
    // }

    // private function generatePivotTable($pivotRelations)
    // {
    //     foreach ($pivotRelations as $relation) {
    //         $code = '';

    //         $camelCaseRelation = Str::ucfirst(Str::camel($relation['pivot']['table']));

    //         $code .= "export default class $camelCaseRelation extends Model {\n";
    //         $code .= "  static primaryKey = ['{$relation['keys']['pivot_foreign_key']}', '{$relation['keys']['pivot_related_key']}'];\n";
    //         $code .= "  static entity = '$camelCaseRelation'\n";

    //         $builder = new PiniaModelBuilder();

    //         foreach ($relation['pivot']['columns'] as $column => $value) {
    //             $code .= $builder->createPiniaAttribute($column, $value);
    //         }

    //         $piniaImports = array_unique($builder->piniaImports);
    //         $piniaHeader = 'import { Model';
    //         foreach ($piniaImports as $import) {
    //             $piniaHeader .= ", $import";
    //         }
    //         $piniaHeader .= " } from 'pinia-orm';\n\n";

    //         // add support for fields

    //         $code .= '}';

    //         File::put(resource_path("js/models/$camelCaseRelation.ts"), $piniaHeader.$code);
    //     }

    //     // class RoleUser extends Model {
    //     //     static entity = 'roleUser'
    //     //     static primaryKey = ['role_id', 'user_id']
    //     //     static fields () {
    //     //       return {
    //     //         role_id: this.attr(null),
    //     //         user_id: this.attr(null)
    //     //       }
    //     //     }
    //     //   }
    // }

    // private function generateCodeForModel($model, $ignoreHidden = false, $filter = [])
    // {
    //     $pivotRelations = [];

    //     $mappings = [
    //         'bigint' => 'number',
    //         'int' => 'number',
    //         'integer' => 'number',
    //         'text' => 'string',
    //         'string' => 'string',
    //         'decimal' => 'number',
    //         'datetime' => 'Date',
    //         'date' => 'Date',
    //         'bool' => 'boolean',
    //         'boolean' => 'boolean',
    //         'json' => '[]',
    //         'array' => 'string[]',
    //         'point' => 'Point',
    //     ];

    //     $piniaMappings = [
    //         'number' => 'Num',
    //         'string' => 'Str',
    //         'boolean' => 'Bool',
    //     ];

    //     $piniaDefaults = [
    //         'number' => '(0)',
    //         'string' => '(\'\')',
    //         'boolean' => '(false)',
    //         'number?' => '(0, { nullable: true })',
    //         'string?' => '(\'\', { nullable: true })',
    //         'boolean?' => '(false, { nullable: true })',
    //     ];

    //     $piniaNullableDefaults = [
    //         'number' => '(0, { nullable: true })',
    //         'string' => '(\'\', { nullable: true })',
    //         'boolean' => '(false, { nullable: true })',
    //     ];

    //     $piniaRelations = [
    //         'HasOne' => 'HasOne',
    //         'HasMany' => 'HasMany',
    //         'BelongsTo' => 'BelongsTo',
    //         'BelongsToMany' => 'BelongsToMany',
    //         // 'HasOneThrough' => 'HasManyBy',
    //         // 'HasManyThrough' => 'HasManyBy',
    //         'MorphOne' => 'MorphOne',
    //         'MorphMany' => 'MorphMany',
    //         'MorphToMany' => 'BelongsToMany',
    //         'MorphTo' => 'MorphTo',
    //     ];

    //     $parser = new ModelParser($model, $ignoreHidden, $filter);

    //     $details = $parser->parse();

    //     preg_match('/.*\\\\(.*)/', $details['model'], $matches);

    //     $modelName = $matches[1];

    //     $entityName = Str::lower($modelName).'s';
    //     $piniaImports = [];
    //     $modelImports = [];
    //     $enumCode = '';
    //     $code = "export default class $modelName extends Model {\n";
    //     $code .= "  static entity = '$entityName'\n";
    //     if (count($details['fields']) > 0) {
    //         $code .= "  // fields\n";
    //         foreach ($details['fields'] as $key => $value) {
    //             $mappedType = $mappings[$value['type']];

    //             $piniaAttribute = 'Attr';
    //             $piniaDefault = '(null)';
    //             if (array_key_exists($key, $details['casts']) && $details['casts'][$key]['casted_as'] === 'enum') {
    //                 $castedField = $details['casts'][$key];
    //                 preg_match('/.*\\\\(.*)/', $castedField['type'], $matches);

    //                 $enumCode .= "export enum {$matches[1]} {\n";
    //                 foreach ($castedField['values'] as $enumKey => $enumValue) {
    //                     $enumCode .= "  $enumKey = $enumValue,\n";
    //                 }
    //                 $enumCode .= "};\n\n";
    //                 $mappedType = $matches[1];
    //             } elseif (array_key_exists($mappedType, $piniaMappings)) {
    //                 $piniaAttribute = $piniaMappings[$mappedType];
    //                 $piniaDefault = $piniaDefaults[$mappedType];
    //                 if ($value['nullable']) {
    //                     $piniaDefault = $piniaNullableDefaults[$mappedType];
    //                 }
    //             }

    //             $piniaImports[] = $piniaAttribute;
    //             $nullable = $value['nullable'] ? ' | null' : '';
    //             $code .= "  @$piniaAttribute$piniaDefault $key!: $mappedType$nullable\n";
    //         }
    //     }
    //     if (count($details['mutators']) > 0) {
    //         $code .= "  // mutators\n";
    //         foreach ($details['mutators'] as $key => $value) {
    //             $mappedType = array_key_exists($value['type'], $mappings) ? $mappings[$value['type']] : 'string';

    //             $piniaAttribute = 'Attr';
    //             $piniaDefault = '(null)';
    //             if (array_key_exists('enum', $value)) {
    //                 preg_match('/.*\\\\(.*)/', $value['type'], $matches);

    //                 $enumCode .= "export enum {$matches[1]} {\n";
    //                 foreach ($value['enum'] as $enumKey => $enumValue) {
    //                     $enumCode .= "  $enumKey = $enumValue,\n";
    //                 }
    //                 $enumCode .= "};\n\n";
    //                 $mappedType = $matches[1];
    //             } elseif (array_key_exists($mappedType, $piniaMappings)) {
    //                 $piniaAttribute = $piniaMappings[$mappedType];
    //                 $piniaDefault = $piniaDefaults[$mappedType];
    //                 if ($value['nullable']) {
    //                     $piniaDefault = $piniaNullableDefaults[$mappedType];
    //                 }
    //             }

    //             $piniaImports[] = $piniaAttribute;
    //             $nullable = $value['nullable'] ? ' | null' : '';
    //             $code .= "  @$piniaAttribute$piniaDefault $key!: $mappedType$nullable\n";
    //         }
    //     }
    //     if (count($details['relations']) > 0) {
    //         $code .= "  // relations\n";
    //         foreach ($details['relations'] as $key => $value) {
    //             if (!array_key_exists($value['type'], $piniaRelations)) {
    //                 continue;
    //             }

    //             $mappedRelation = $piniaRelations[$value['type']];

    //             $piniaType = $value['model'];

    //             $piniaType .= substr($key, -1) === 's' ? '[]' : '';

    //             $lowercaseName = Str::lower($modelName);
    //             $relationshipDefault = "(() => {$value['model']}, '{$lowercaseName}_id')";

    //             if ($mappedRelation === 'HasOne' || $mappedRelation === 'HasMany') {
    //                 $modelImports[] = $value['model'];
    //                 $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['local_key']}')";
    //             } elseif ($mappedRelation === 'MorphOne') {
    //                 if (!array_key_exists($value['model'], $this->morphRelationships)) {
    //                     $this->morphRelationships[$value['model']] = [];
    //                 }

    //                 $relationName = explode('_', $value['keys']['morph_type'])[0];

    //                 if (!array_key_exists($relationName, $this->morphRelationships[$value['model']])) {
    //                     $this->morphRelationships[$value['model']][$relationName] = [];
    //                 }
    //                 $modelImports[] = $value['model'];
    //                 $this->morphRelationships[$value['model']][$relationName][] = $modelName;
    //                 $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}', '{$value['keys']['local_key']}')";
    //             } elseif ($mappedRelation === 'BelongsToMany') {
    //                 $camelCasePivot = str_replace('_', '', ucwords($value['pivot']['table'], '_'));
    //                 $pivotRelations[$value['pivot']['table']] = $value;
    //                 $modelImports[] = $value['model'];
    //                 $relationshipDefault = "(() => {$value['model']}, () => $camelCasePivot, '{$value['keys']['pivot_foreign_key']}', '{$value['keys']['pivot_related_key']}', '{$value['keys']['parent_key']}', '{$value['keys']['related_key']}')";
    //             } elseif ($mappedRelation === 'BelongsTo') {
    //                 $modelImports[] = $value['model'];
    //                 $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['owner_key']}')";
    //             } elseif ($mappedRelation === 'MorphTo') {
    //                 // Add models to array, and type
    //                 $piniaType = "<MORPHTYPE:$modelName:$key>";
    //                 $relationshipDefault = "(() => <MORPH:$modelName:$key>, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}')";
    //             } elseif ($mappedRelation === 'MorphMany') {
    //                 if (!array_key_exists($value['model'], $this->morphRelationships)) {
    //                     $this->morphRelationships[$value['model']] = [];
    //                 }

    //                 $relationName = explode('_', $value['keys']['morph_type'])[0];

    //                 if (!array_key_exists($relationName, $this->morphRelationships[$value['model']])) {
    //                     $this->morphRelationships[$value['model']][$relationName] = [];
    //                 }

    //                 $this->morphRelationships[$value['model']][$relationName][] = $modelName;

    //                 $modelImports[] = $value['model'];
    //                 $relationshipDefault = "(() => {$value['model']}, '{$value['keys']['foreign_key']}', '{$value['keys']['morph_type']}', '{$value['keys']['local_key']}')";
    //             }

    //             $piniaImports[] = $mappedRelation;

    //             $code .= "  @$mappedRelation$relationshipDefault $key!: $piniaType\n";
    //         }
    //     }

    //     $code .= "}\n";

    //     $piniaHeader = 'import { Model';
    //     foreach (array_unique($piniaImports) as $import) {
    //         $piniaHeader .= ", $import";
    //     }
    //     $piniaHeader .= " } from 'pinia-orm';\n";

    //     foreach ($modelImports as $model) {
    //         $piniaHeader .= "import $model from './$model';\n";
    //     }

    //     foreach ($pivotRelations as $relation) {
    //         $camelCasePivot = str_replace('_', '', ucwords($relation['pivot']['table'], '_'));
    //         $piniaHeader .= "import $camelCasePivot from './$camelCasePivot';\n";
    //     }

    //     $code = "$piniaHeader\n$enumCode$code";

    //     File::put(resource_path("js/models/$modelName.ts"), $code);

    //     return $pivotRelations;
    // }

    // private function camelize($input): string
    // {
    //     return str_replace('_', '', ucwords($input, '_'));
    // }
}

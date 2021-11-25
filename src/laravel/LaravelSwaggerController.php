<?php

namespace vpashkov\represent\laravel;

use Illuminate\Routing\Controller;
use vpashkov\represent\core\Represent;
use vpashkov\represent\core\Schema;
use vpashkov\represent\helpers\H;


class LaravelSwaggerController extends Controller
{
    public function __construct()
    {


    }

    public function swagger($representNs)
    {

        $swagger = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Migtorg admin',
                'version' => '3.0.0',
            ],
            'servers' => [['url' => '']],
            'tags' => [],
            'paths' => [],
            'components' => [
                'schemas' => [
                    'RepresentRequestSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'schema' => [
                                'type' => 'object',
                                'example' => ['order' => 'id'],
                                'properties' => [
                                    'where' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'string',
                                                'example' => 'name = \'myName\' OR #myCondition',
                                            ],
                                            [
                                                'type' => 'object',
                                                'example' => ['name' => 'myName', 'status.code' => ['active', 'inactive']],
                                            ],
                                        ],
                                        'description' => 'SQL-like where syntax with #parameters, example: "name = \'myName\'"  OR conditioning object, example: {name: \'myName\'}',
                                    ],
                                    'whereMyCondition' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'object',
                                                'example' => ['field' => 'value'],
                                            ],
                                            [
                                                'type' => 'string',
                                                'example' => 'field <> \'value\'',
                                            ],

                                        ],
                                    ],
                                    'order' => [
                                        'type' => 'string',
                                        'description' => 'SQL-like order syntax, example: "type.name ASC, status.serial DESC"',
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                    ],
                                    'offset' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],

                        ],
                    ],
                    'RequestSchema' => [
                        'type' => 'object',
                        'example' => ['order' => 'id'],
                        'properties' => [
                            'where' => [
                                'anyOf' => [
                                    [
                                        'type' => 'string',
                                        'example' => 'name = \'myName\' OR #myCondition',
                                    ],
                                    [
                                        'type' => 'object',
                                        'example' => ['name' => 'myName', 'status.code' => ['active', 'inactive']],
                                    ],
                                ],
                                'description' => 'SQL-like where syntax with #parameters, example: "name = \'myName\'"  OR conditioning object, example: {name: \'myName\'}',
                            ],
                            'whereMyCondition' => [
                                'anyOf' => [
                                    [
                                        'type' => 'object',
                                        'example' => ['field' => 'value'],
                                    ],
                                    [
                                        'type' => 'string',
                                        'example' => 'field <> \'value\'',
                                    ],

                                ],
                            ],
                            'order' => [
                                'type' => 'string',
                                'description' => 'SQL-like order syntax, example: "type.name ASC, status.serial DESC"',
                            ],
                            'limit' => [
                                'type' => 'integer',
                            ],
                            'offset' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],

                ],
                'parameters' => [

                    'DictParameter' => [
                        'name' => 'dicts',
                        'in' => 'query',
                        'description' => 'Grab dictionary in response',
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                            'default' => true,
                        ],
                    ],
                    'countParameter' => [
                        'name' => 'dict',
                        'in' => 'query',
                        'description' => 'Grab count of records in response',
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                            'default' => true,
                        ],
                    ],
                ],
            ],
        ];
        [$paths, $tags, $schemas] = $this->collectRepresents($representNs);
        $swagger['paths'] = array_merge($swagger['paths'], $paths);
        $swagger['tags'] = array_merge($swagger['tags'], $tags);
        $swagger['components']['schemas'] = array_merge($swagger['components']['schemas'], $schemas);
        return $swagger;
    }


    public function collectRepresents($representNs)
    {
//        $representNs = 'App\Modules\Admin\Represents';
        $fullClassMap = require(base_path() . '/vendor/composer/autoload_classmap.php');
        $classes = [];
        foreach ($fullClassMap as $class => $path) {
            if (strpos($class, $representNs) !== false) {
                $classes[] = $class;
            }
        }
        $tags = [];
        $paths = [];
        $schemaDescriptions = [];
        foreach ($classes as $class) {
            $module = explode("\\", $class)[2];
            $model = explode("\\", $class)[4];
            $action = explode("\\", $class)[5];
            $representName = "$module/$model/$action";
            $schemaName = "$module.$model.$action";
            $represent = new $class();
            if ($represent->private === true) {
                continue;
            }

            $tags[] = ['name' => $representName];

            $schemaDescription = $this->collectSchema($represent->schema, $represent);
            $dictsSchemasInReqeust = [];
            foreach ($represent->dictSchemas() as $dictName => $dictSchema) {
                if (H::get($dictSchema, $represent->config->singletonFlag) === true) {
                    continue;
                }
                $dictsSchemasInReqeust[$dictName] = $this->collectDictSchema($represent->createDictSchema($dictName),
                    H::get($represent->dictSchemas()[$dictName], '#get', 'all'));
            }
            $dictSchemaDescription = ['type' => 'object'];
            if (count($dictsSchemasInReqeust) > 0) {
                $dictSchemaDescription = ['type' => 'object', 'properties' => $dictsSchemasInReqeust];
            }

            if ($represent->schema->can('r')) {
                if ($represent->onlyMultiply === false) {
                    $paths["/represent/one?represent=$representName"] = $this->generateOneRequest($represent, $representName, $schemaName,
                        $schemaDescription, $dictSchemaDescription);
                }
                if ($represent->multiply === true) {
                    $paths["/represent/all?represent=$representName"] = $this->generateAllRequest($represent, $representName, $schemaName,
                        $schemaDescription, $dictSchemaDescription);
                }
            }
            if ($represent->schema->can('c') || $represent->schema->can('u')) {
                $paths["/represent/save?represent=$representName"] = $this->generateSaveRequest($represent, $representName, $schemaName,
                    $schemaDescription, $dictSchemaDescription);
            }
            if ($represent->schema->can('d')) {
                $paths["/represent/delete?represent=$representName"] = $this->generateDeleteRequest($represent, $representName, $schemaName,
                    $schemaDescription, $dictSchemaDescription);
            }
            if ($represent->multiply === true) {
                $paths["/represent/count?represent=$representName"] = $this->generateCountRequest($represent, $representName,
                    $schemaDescription, $dictSchemaDescription);
            }

            $schemaDescriptions[$schemaName] = $schemaDescription;

            $dictSchemasNames = [];
            foreach ($represent->dictSchemas() as $dictName => $dictSchema) {
                $dictSchemaName = $schemaName . '.' . $dictName;
                $dictSchemasNames[$dictName] = $dictSchemaName;
                $dictSchema = $this->collectDictSchema($represent->createDictSchema($dictName),
                    H::get($represent->dictSchemas()[$dictName], '#get', 'all'));
                $paths["/represent/dict?represent=$representName&dict=$dictName"] = $this->generateDictRequest($represent, $representName,
                    $dictName, $dictSchema, $dictSchemaName);

                $schemaDescriptions[$dictSchemaName] = $dictSchema;
            }

            if (count($represent->dictSchemas()) > 0) {

                $paths["/represent/dicts?represent=$representName"] = $this->generateDictsRequest($represent, $representName,
                    $dictSchemasNames);

            }

        }
//        var_dump($paths);


        return [$paths, $tags, $schemaDescriptions];
    }

    public function collectSchema(Schema $schema, ?Represent $represent = null, $path = '')
    {
        $typeMap = [
            'json' => 'object',
        ];
        $description = ['type' => 'object', 'properties' => []];
        foreach ($schema->fields as $fieldName => $type) {
            $type = $type['dataType'];
            if (array_key_exists($type, $typeMap)) {
                $type = $typeMap[$type];
            }
            if ($this->isNotExcludedField($represent, $path, $fieldName)) {
                $description['properties'][$fieldName] = [
                    'type' => $type,
                ];
            }
        }
        $separator = $path === '' ? '' : '.';
        foreach ($schema->relations as $relationName => $relation) {
            if ($this->isNotExcludedField($represent, $path, $relationName)) {
                if ($relation->relationSchema['multiple'] === true) {
                    $description['properties'][$relationName] = [
                        'type' => 'array',
                        'items' => $this->collectSchema($relation, $represent, $path . $separator . $relationName),
                    ];
                } else {

                    $description['properties'][$relationName] = $this->collectSchema($relation, $represent,
                        $path . $separator . $relationName);
                }
            }
        }
        if ($represent !== null) {
            foreach ($represent->includeFieldsInDoc() as $field => $value) {
                $fieldParts = explode('.', $field);
                $fieldName = array_pop($fieldParts);
                $fieldPath = implode('.', $fieldParts);

                if ($path === $fieldPath) {

                    $description['properties'][$fieldName] = $value;
                }
            }
        }
        return $description;
    }

    public function isNotExcludedField($represent, $path, $field)
    {
        if ($represent === null) {
            return true;
        }
        $separator = $path === '' ? '' : '.';
        $fullName = $path . $separator . $field;
        if (array_key_exists($fullName, $represent->excludeFieldsFromDoc())) {
            return false;
        }
        return true;
    }

    public function collectDictSchema(Schema $schema, $get)
    {
        if ($get === 'one') {
            $data = $this->collectSchema($schema);
        }
        if ($get === 'all') {
            $data = ['type' => 'array', 'items' => $this->collectSchema($schema)];
        }
        $description = [
            'type' => 'object',
            'properties' => [
                'data' => $data,
                'count' => [
                    'type' => 'integer',
                ],
            ],
        ];
        return $description;
    }

    public function generateOneRequest(Represent $represent, $representName, $schemaName, $schemaDescription, $dictsSchemaDescription)
    {
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Return first entity of represent $representName",
                'parameters' => [
                    ['$ref' => '#/components/parameters/DictParameter'],
//                    ['$ref' => '#/components/parameters/RequestSchema'],
                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/RepresentRequestSchema'],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
//                                        'data' => $schemaDescription,
                                        'data' => ['$ref' => "#/components/schemas/$schemaName"],
                                        'dicts' => $dictsSchemaDescription,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }

    public function generateAllRequest(Represent $represent, $representName, $schemaName, $schemaDescription, $dictsSchemaDescription)
    {
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Return all entity of represent $representName",
//                'parameters' => [
//                    ['$ref' => '#/components/schemas/DictParameter'],
//                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/RepresentRequestSchema'],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
//                                        'data' => ['type' => 'array', 'items' => $schemaDescription],
                                        'data' => ['type' => 'array', 'items' => ['$ref' => "#/components/schemas/$schemaName"]],
                                        'dicts' => $dictsSchemaDescription,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }

    public function generateSaveRequest(Represent $represent, $representName, $schemaName, $schemaDescription, $dictsSchemaDescription)
    {
        $validators = [];
//        var_dump($represent->validators());
        foreach ($represent->validators() as $validatorName => $validator) {
            $validators [$validatorName] = [
                'type' => 'object',
                'properties' => ['field' => ['type' => 'string'], 'text' => ['type' => 'string']],
            ];
        }
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Create or update entity of represent $representName",
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => ['row' => ['$ref' => "#/components/schemas/$schemaName"]],
//                                'oneOf' => [
//                                    [
//                                        'type' => 'object',
//                                        'properties' => ['row' => ['$ref' => "#/components/schemas/$schemaName"]],
//                                    ],
//                                    [
//                                        'type' => 'object',
//                                        'properties' => ['rows' => ['type' => 'array', 'items' => ['$ref' => "#/components/schemas/$schemaName"]]],
//                                    ],
//                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'action' => ['type' => 'string', 'enum' => ['c', 'u']],
                                        'status' => ['type' => 'string', 'enum' => ['OK']],
                                        'row' => ['$ref' => "#/components/schemas/$schemaName"],
//                                        'sourceRow' => ['$ref' => "#/components/schemas/$schemaName"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        if (count($validators) > 0) {
            $descritption['post']['responses'][400] = [
                'description' => 'validation error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => ['type' => 'string', 'enum' => ['FAIL']],
                                'errors' => [
                                    'type' => 'object',
                                    'properties' => $validators,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $descritption;
    }

    public function generateDeleteRequest(Represent $represent, $representName, $schemaName, $schemaDescription, $dictsSchemaDescription)
    {
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Return all entity of represent $representName",
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => ['row' => $schemaDescription],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'action' => ['type' => 'string', 'enum' => ['c', 'u']],
                                        'status' => ['type' => 'string', 'enum' => ['OK', 'FAIL']],
//                                        'row' => ['type' => 'object', 'properties' => ['id' => 'string']],
                                        'sourceRow' => $schemaDescription,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }

    public function generateCountRequest(Represent $represent, $representName, $schemaDescription, $dictsSchemaDescription)
    {
        $descritption = [
            'get' => [
                'tags' => [$representName],
                'summary' => "Return count entity of represent $representName",

                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'number',
                                    'description' => 'Count of entities',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }

    public function generateDictRequest(Represent $represent, $representName, $dictName, $dictSchema, $dictSchemaName)
    {
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Return dictionary \"$dictName\" of represent $representName",
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    $dictName . "Schema" => ['$ref' => '#/components/schemas/RequestSchema'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
//                                'schema' => $dictSchema,
                                'schema' => ['$ref' => "#/components/schemas/$dictSchemaName"],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }

    public function generateDictsRequest(Represent $represent, $representName, $dictsSchemas)
    {
        $dicts = [];
        foreach ($dictsSchemas as $dictName => $schemaName) {
            $dicts[$dictName] = ['$ref' => "#/components/schemas/$schemaName"];
        }
        $descritption = [
            'post' => [
                'tags' => [$representName],
                'summary' => "Return all dictionaries of represent $representName",
                'responses' => [
                    200 => [
                        'description' => 'success',
                        'content' => [
                            'application/json' => [
//                                'schema' => $dictSchema,
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $dicts,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $descritption;
    }


}


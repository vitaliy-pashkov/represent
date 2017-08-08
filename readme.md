Yii2-Represent
====


Yii2 extension for CRUD operations with data structures in relational databases.

Features:

  - Declarative style of describing the data structure
  - Full CRUD functionality for data structures
  - Limit, offset, count working as you want
  - REST API

Install
--

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run:
```
php composer.phar require --prefer-dist vpashkov/yii2-represent
```

or add

```json
"vpashkov/yii2-represent": "~1.0.0"
```

to the require section of your composer.json file.

Usage
--

Before use Represent need to generate ActiveRecord models for tables

### 1. Instead ActiveRecord

```php
$userRepresent = new \vpashkov\represent\Represent([      //declaration
    '#model' => User::class,
    '#actions' => 'crud',
    '#limit' => 10,
    '*',
    'orders' => [
        'status',
    ]
]);
$users = $userRepresent->getAll();          //selection
$users[0]['orders'][0]['status'] = 'new';   //modification
$userRepresent->saveAll($users);            //saving
```

### 2. Inheritance of classes from Represent

* Create class in namespace `app\represents\MODEL_NAME` or `app\modules\MODULE_NAME\represents\MODEL_NAME`
* Describe the structure by `getMap()`
```php
namespace app\represent\user;
class Edit extends \vpashkov\represent\Represent
    {
    public function getMap()
        {
        return [
            '#model' => User::class,
            '#actions' => 'crud',
            '#limit' => 10,
            '*',
            'orders' => [
                'status',
            ]
        ];
        }
    }
```
#### 2.1 Create Represent object by name and use CRUD methods

```php
$userRepresent = Represent::create('user/edit');
$users = $userRepresent->getAll();          //selection
$users[0]['orders'][0]['status'] = 'new';   //modification
$userRepresent->saveAll($users);            //saving
```
#### 2.2 Create api controller extends RepresentController

Call CRUD actions:  
GET /api/all?represent=user/edit  
GET /api/one?represent=user/edit  
POST /api/save?represent=user/edit  
POST /api/delete?represent=user/edit  

Represent API
--

#### Methods
| Scope | Method | Description |
| --------| -------- | -------- |
| public | __construct(`$map = false`, `$options=[]`) | Constructor |
| public | setMap(`$map`) | Overrides the data structure |
| public | getAll() | Returns an array of data structures |
| public | getOne() | Returns the data structure |
| public | getCount() | Returns the count of data structures with considering `#offset` and `#where` relating data |
| public | getMeta() | Returns statistics by structure |
| public | getDicts() | Returns dictionaries |
| public | getDict($dictName) | Returns the dictionary by name |
| public | saveAll(`$rows`) | Returns an array of storage statuses and new structures |
| public | saveOne(`$row`) | Returns the save status and the new structure |
| public | deleteAll(`$rows`) | Returns an array of deletion statuses and structures that contain only the primary key of the deleted records|
| public | deleteOne(`$row`) | Returns the delete status and a structure that contains only the primary key of the deleted record |
| protected | getMap() | Overriding the method allows describe the data structure in an inherited class. Return `$map` |
| protected | getDictMaps() | Overriding the method allows describe the structure of dictionaries in an inherited class. Return hash array ```['dictName' => $dictMap]``` |
| protected|  getDefaultOptions() | Overriding the method allows describe the default parameters. Return parameters array |
| protected | process(`$rows`) | Overriding the method allows process data after selecting. Return `$rows`|
| protected | deprocess(`$row`) | Overriding the method allows process data before saving or deleting. Return `$row`|
| protected | processDICT_NAME($dict) | The method definition allows process the dictionary data after a selecting |

#### Properties
| Scope | Property | Description |
| --------| -------- | -------- |
| public | $maxLimit = 1000000 | Int, mximum limit. Used to prevent DoS-attacks  |
| public | $options | Array, Options collect from `getDefaultOptions()`, GET and POST request parameters and $options argument in constructor. |
| protected | $collectRequestOptions = true | Boolean, if true, Represent collect GET and POST request parameters in $options |


### Description of the data structure $map

The data structure is described by an associative array of PHP.  
The special field key begins with the `#`.  
The fields of the table indicated without a key.   
Linked tables indicated by the key - the ActiveRecord relation name.   
There is no need to specify primary and foreign keys - they will be added automatically.

The data structure is stored in the Represent object and is used for CRUD methods.
You can override the data structure using the `setMap($newMap)`
  

#### Recommended algorithm for describing the structure:
* Specify the root `#model`
* Specify `#actions`, which Represent can do with this table
* Specify selection rules `#where`, `#order`, `#limit`, `#offset`
* Specify the fields to select from the table
* Specify relations
  - specify `#actions`, which Represent can do with this table
  - specify selection rules `#where`
  - specify the fields to select from the table
  - repeat recursively for all related models
   
#### Full example:
```php
new Represent([
    // special field
    '#model' => Table1::class,
    '#actions' => 'crud',
    '#whereId' => 'id = 1',
    '#where1' => ['table2s.field1' => 'some_value'],
    '#order' => 'table2.field1',
    '#limit' => 10,
    '#offset' => 10,
    // fields of table1
    'field1',
    'field2',
    'field3',
    // relation table2. Key 'table2s' - relation name of ActiveRecord
    'table2s' => [
        // special field
        '#actions' => 'crud',
        // fields of table2
        'field1',
        'field2',
        // relation table3.
        'table3' => [
            '#actions' => 'crud',
            '*', // select all fields
        ],
    ],
    //relation table4.
    'table4' => [
        //if not specify any fields, Represent select only primary key
    ]
]);
```

#### Special field in description of the data structure

##### * 
Specify without key. Select all fields from current table.

##### #model
Root model class name.  
Required field for the root model. In child structures it is ignored.
##### #actions
A string that can contain characters 'crud'. The presence of a character allows the corresponding action with this model.
Optional field. By default: `'r'`
##### #where...
The array key, starting with '#where' describes the conditions. It is possible to describe several conditions in this way:
```
'#whereId' => ['id' => 1],
'#whereStatusNew'  => 'status = "new"'
```
Conditions are combined by the 'AND' operator   
The value can be a SQL string or array in hash format Yii (operator format is not yet supported)
The conditions specified in the child models are added to the condition JOIN ON    
Optional field.
##### #order
Fields by which the sample is sorted. SQL string or array in hash format.  
Specified only for the root model.  
Optional field.

##### #limit 
The number of records from root model to select. Related models do not affect the number of records.  
Specified only for the root model.    
Optional field.

##### #offset 
The number of the first records from root model that are skipped. Related models do not affect at offset.  
Specified only for the root model.      
Optional field.  

##### Access to the fields of tables in the values of special fields #where и #order
Regardless of the position of the special field, it possible refer to any field of the child table by the full path to this field.  
The root table fields are specified without a prefix: `id`, `field1`  
Fields of child tables: `table2s.field1`, `table2s.table3.field1`  
Thus, a conflict of names is not possible.  
In the string values, the field names must be separated by spaces, including brackets.  


### Parameterization of data structure

#### `$options`
When inheriting from Represent and describing the data structure using the `getMap ()` method, the data structure should be described in a general way.

Example:
```php
namespace app\represent\user;
class View extends Represent
    {
    public function getDefaultOptions()
        {
        return [
            'id' => null,
        ];
        }
    
    public function getMap()
        {
        return [
            '#model' => User::class,
            '#where' => ['id' => $this->options['id']]
            '*'
        ];
        }
    }
```

`$this->options['id']` can be obtained from the GET or POST request parameters or from the class constructor.

#### `$options['map']`

Some query parameters can be passed to `$map` automatically from` $options['map'] `   
`$options['map']` - json string with the following structure:
 
```json
{
  "filter": [FilterData], //Filter data structure 
  "where": [WhereType], //Similarly #where
  "order":  [OrderType], //Similarly #order
  "limit": [int], 
  "offset":  [int]
}
```
All fields is optional.

### Selecting data

Selecting methods `getAll()` and `getOne()`  
* Eager loading by one SQL request, creating by ActiveQuery methods;    
* Related tables are selected using LEFT JOIN;
* \#where of relation tables add at ON condition;    
* \#limit is implemented using a subquery in FROM;  
* After the query is executed, data structures are constructed by the algorithm with complexity N at best and N * M / 2 in the worst case (where N is the number of rows in the sample, M is the total number of data structures).
* After building the data structure, calling `process($rows)`, which allows you to further process the data
* ActiveRecord querying data life cycle is not implemented  
* DB connection get from `getDb()` of root model


### Saving data

Saving methods `saveAll($rows)` and `saveOne($row)`
* Saving implemented by ActiveRecord, therefore [ActiveRecord Saving Data Life Cycle](http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#saving-data-life-cycle) is implemented
* Automatically determines the order in which save the models to avoid foreign key conflicts
* Saving only fields specified in `$map` (including `*`)
* Record insert only if `#actions` include create flag - `c`, otherwise the action is ignored
* Record update only if `#actions` include update flag - `u`, otherwise the action is ignored
* Flags in data:
    * **`'#delete' => true`** adding this flag, delete current and child records if `#actions` contains `d`
    * **`'#unlink' => true`** adding this flag, call `unlink()` method from related models

### Delete data

Delete methods `deleteAll($rows)` and `deleteOne($row)`
* Saving implemented by ActiveRecord, therefore [ActiveRecord Deleting Data Life Cycle](http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#deleting-data-life-cycle) is implemented
* Automatically determines the order in which delete the models to avoid foreign key conflicts
* Record delete only if `#actions` include delete flag - `d`, otherwise the action is ignored

### Dictionaries

When working with data structures, it often becomes necessary to access data that is not directly part of the structure, but is in one way or another affiliated with it.  
For example, to form a select related model, you need a list of all models.  
  
Dictionaries are data structures described in the `getDictMaps()` method, which can be selected using `getDicts()` and `getDict($ dictName)`.    
Data structures are described in a similar way, with a few exceptions:
* The field `#action` is ignored; Dictionaries can only select
* The field `#singleton` boolean; if true, dictionary not selected by `getDicts()`; It makes sense for the dictionary structure to be parameterized  

After the dictionary is selected, call the method `processDICT_NAME($rows)` (where DICT_NAME = ucfirst(dict name)) if it exist. 


RepresentController API
--

RepresentController implement REST API for Represent methods.  
For use create controller extends RepresentController.


| Scope | Method | Description |
| --------| -------- | -------- |
| public | actionOne($represent, $dicts = false) | Return json Represent->getOne(); if $dicts == true add Represent->getDicts() |
| public | actionAll($represent,  $count = false, $meta = false, $dicts = false) | Return json Represent->getAll(); optionally adds getCount(), getDicts(), getMeta() |
| public | actionSave($represent) | In POST parameters find `rows` or `row` and call Represent->saveAll($rows) or $represent->saveOne($row) respectively. Return json save statuses|
| public | actionDelete($represent) | In POST parameters find `rows` or `row` and call Represent->deleteAll($rows) or $represent->deleteOne($row) respectively. Return json delete statuses|
| public | actionDicts($represent) | Return json Represent->getDicts() |
| public | actionDict($represent, $dictName) | Return json Represent->getDict($dictName) |
| public | actionCount($represent) | Return json Represent->getCount() |
| public | actionMeta($represent) | Return json Represent->getMeta() |

Argument $represent is - Represent name, Formed as follows:  
Two-syllable name:  
`'user/view-all'` - create object of `\app\represents\user\ViewAll`  

Three-syllable name:  
`'admin/user/viewAll'` - create object of `\app\modules\admin\represents\user\ViewAll`

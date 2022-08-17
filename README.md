# This work is based on https://github.com/fumeapp/modeltyper, credit to them.

### To use
* Install pinia-orm if you haven't already `npm i pinia-orm`
* Run `artisan piniamodels:generate`
* Code will be generated into `resources/js/models` by default

### Modifying

By default, hidden fields are excluded and all other fields, mutators and relationships are included

* Create a new command 
* Add the following code
```php
use Dev1437\PiniaModelGenerator\PiniaModelsBuilder;

// Default
$pmb = new PiniaModelsBuilder();
// Specify a different path e.g. to have the model put in resources/ts/pinia
$pmb = new PiniaModelsBuilder(resource_path('ts/pinia')); 
// Generate code for specific models
use App\Models\User;
use App\Models\Post;

$pmb = new PiniaModelsBuilder(null, [], [
    User::class,
    Post::class
]);
// Specify options for specific model e.g. Include hidden fields and remove email_verified_at from User
use App\Models\User;

$pmb = new PiniaModelsBuilder(null, [
    User::class => [
        'ignoreHidden' => false,
        'filters' => [
            'email_verified_at',
        ],
    ]
]);

$pmb->buildModels();
```

### Custom code in generated models
Each model contains two blocks 
```
...
/* --- user header --- */
/* --- end user header --- */
...
/* --- user code --- */
/* --- end user code --- */
...
```
Any code written in these blocks will be persisted when the models are regenerated

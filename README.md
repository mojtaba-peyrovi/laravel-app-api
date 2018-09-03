# new series : restful api with laravel:
======================
just skipped the introduction. (part1-part5)
## part 6-03:
- in .env file we need to change the app url from localhost to laravel-app-api.test
- then if providers/routeServiceProviders we go ahead and remove prefix from mapApiRoutes method.
- then we remove all default routes from both web and api routes in routes folder.

----
## part 7:
**** important;  when we have a model which is a part of another resource we can go into it and instead of saying:  class Buyer extends Model  if the Buyer is a subset of users we can say class buyer extends User. and since we dont use Model class anymore we remove this line: use Illuminate\Database\Eloquent\Model;
 mkaing routes for the api:
- because buyers are a subset of users we dont need to be able to create buyers or delete them or edit, because we should only do those to users.
in this case we can specify which methods we need to use. like this:  Route::resource('buyers', 'Buyer\buyerController', ['only' => ['show', 'index']]);
- we can also use expect instead of only which is another way to manage which routes we want to be available.
as a review we can check the routes we made in terminal using php artisan route:list
---
## part 8:
- in order for the fields to be able to be assigned in mass using Create model, we need to specify which fields can be $fillable. if we want to have all the fields in $fillable array, we can instead use $guarded=[ ];
- these $fillable, $guarded, $hidden or mabye we have more of them that I don't know yet are called attributes. for example if we want to hide any field from a json response we can add that field to $hidden array.
- for user model we can add verification token for email verification token sent from website to the user. it has to be added to both fillable and hidden attributes.
- In order to check wether a user is admin or not we can define costants that would never change and define methods in model classes to check if the value is true in database, the function returns true. etc. we can do this to some other fields as well to make it easier to use.
- there is an easy way to make a verification code like this:  
public function generateVerifiedCode()
    {
        return str_random(40);
    }
this method returns a random token consisting of 40 characters.
- for many to many relationships we need to use BelongsToMany() relationship and also we need to use pivot tables.
- who belongs to who? the model who has the foreign key always belongs to the one with primary key.
---

## part 9:
- the reason why we add Schema::defaultStringLength(191); to app serviceprovider:
because laravel has made charset value in config/database.php under mysql, utfmb4 that makes string values 45 times more than 255 which is the default for charset.
and since mysql accepts maximum of 787 characters, when we want to migrate, it returns the error of :
Specified key was too long error
- for many to many relationship we need to make a pivot table. in order to name it, we need to name it as  model1_model2   the alphabetical order has to be applied for this name. for example if we want to make a pivot for category and products we should make the migration like this;
php artisan make:migration category_product_table --create=category_product
the migratuib of category_product_table makes a table called category_table  we need to use the names singular as models are not tables which are plural. for example we dont say categories_products
- inside the pivot migration we just need to add the foreign keys of both models.
---

## part 10:
- in order to use one of the existing users lets say, to use them in seller_id factory we can say like this: 'seller_id' => $faker->User::all()->random()->id,
  or we can say:  User::inRandomOrder()->first()->id;

- when we want to make a transaction factory we need to make sure the user who makes transaction has at least one product.
we can say: $seller = Seller::has('products')->get()->random();

after defining all factories we just need to call them in seeder:
- before using the seeder we should empty all tables in the database.   we can do it like this in the seeder:  User::truncate();
- in order to empy the pivot table we have to disable foreign key checks like this: DB::statement('SET FOREIGN_KEY_CHECKS = 0');
- next in seeder we need to specify how many of each factory instance we want to generate. like this:
        $usersQuantity = 200;
        $categoriesQuantity = 30;
        $productsQuantity = 1000;
        $transactionsQuantity = 1000;
finally we will say factory(User::class, $usersQuantity);  
for products we need to attach them category names randomly. like this:
factory(Product::class, $usproductsQuantity)->create()->each(
            function($product){
                $categories = Category::all()->random(mt_rand(1,5))->pluck('id');
                $products->categories()->attach($categories);
            });
**** this is so important, when I was running the seeder, I got the error that seller table doesnt exist. and we shouldnt have a seller table as it is a subst of users table.
in order to get around this error we need to manually specify in Seller model that the table name is user. like this: protected $table = 'users';
we also do this for buyers.
- we can run the migration and seeder two ways : 1) php artisan migrate:refresh    and then     php artisan db:seed     or 2) php artisan migrate:refresh  --seed


## part 11:
---
- when we want to make index method in controller for api instead of returning view, we should return a response as json. like this:
public function index()
    {
        $users = User::all();
        return response()->json(['data'=> $user], 200);
    }
200 is the response code saying everything is working as expected.
we could also say return $users but it would return the collection without the structure.

## part 12,13:
 ---
a useful method:  isDirty(array|string|null $attributes = null)
Determines if the model or given attribute(s) have been modified.
## part 14:

Accessors and mutators allow you to format Eloquent attribute values when you retrieve or set them on model instances.
mutator can modify the value after retrieving date from database but accessors just return a modified data.
how to name mtator functions?  
```
set+<field_name>+Attribute()
```

here is an example of a mutator:
```
public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
    }
```    

for accessors we should name it this way:   
```
get + <field_name> + Attribute()
```
example for accessor:
```
public function getNameAttribute($name)
    {
        return ucword($name);
    }
```    
in this part we made simple mutators to change name and email values to lowercase. with a new seeding we can see the results.

- now we are going to make a base controller for api and  all other controllers we make are going to extend from that not from controller.php
- then we make a folder called traits under app folder and inside traits we make a new file called: ApiResponse.php

train definition from medium.com:

*One of the problems of PHP as a programming language is the fact that you can only have single inheritance. This means a class can only inherit from one other class. For example, it might be desirable to inherit methods from a couple of different classes in order to prevent code duplication. In PHP 5.4 a new feature of the language was added known as Traits and are used extensively in the Laravel Framework.*
([full article](https://medium.com/@kshitij206/traits-in-laravel-5db8beffbcc3))
- inside ApiResponse trait we add some methods for managing error and success responses, like this:
```
<?php

namespace App\Traits;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait ApiResponser
{
    private function successResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    protected function errorResponse($message, $code)
    {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }
    protected function showAll(Collection $collection, $code = 200)
    {
        return $this->successResponse(['data' => $collection], $code);
    }

    protected function showOne(Model $model, $code = 200)
    {
        return $this->successResponse(['data' => $model], $code);
    }
}
```
in order to have the trait available inside each controller we can import it manually into each controller but its not so practical. instead we can import the trait into ApiController (remember we got all controllers extend ApiController)
- now we simply say:
```
<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use ApiResponser;
}
```
this way the ApiResponser trait is available in all controllers.

As we remember, the response line repeated in all controller methods, so now we can use showAll() and showOne() methods from ApiResponser trait.

For methods returning a collection we can use showAll() and for those returning a single instance we can use showOne().

like this:

##### showAll():
```
before:  

return response()->json(['data' => $buyers], 200);

after:

return $this->showAll($buyers);
```

##### showOne():
```
before:

return response()->json(['data' => $buyer], 200);

after:

return $this->showOne($buyer);
```

we have some error responses inside UserController that can be called using ApiResponser trait. like this:
```
before:

 return response()->json(['error'=>'Only verified user can modify the admin field','code'=>409], 409);

after:
return $this->errorResponse('You need to specify a different value to update',422);   

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

## part 9:
---
- the reason why we add Schema::defaultStringLength(191); to app serviceprovider:
because laravel has made charset value in config/database.php under mysql, utfmb4 that makes string values 45 times more than 255 which is the default for charset.
and since mysql accepts maximum of 787 characters, when we want to migrate, it returns the error of :
Specified key was too long error
- for many to many relationship we need to make a pivot table. in order to name it, we need to name it as  model1_model2   the alphabetical order has to be applied for this name. for example if we want to make a pivot for category and products we should make the migration like this;
php artisan make:migration category_product_table --create=category_product
the migratuib of category_product_table makes a table called category_table  we need to use the names singular as models are not tables which are plural. for example we dont say categories_products
- inside the pivot migration we just need to add the foreign keys of both models.

## part 10:
---
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
---
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
```
 return response()->json(['data' => $buyer], 200);
```
 after:
```
 return $this->showOne($buyer);
```
 we have some error responses inside UserController that can be called using ApiResponser trait. like this:
```
before:
```
  return response()->json(['error'=>'Only verified user can modify the admin field','code'=>409], 409);
```
 after:
```
return $this->errorResponse('You need to specify a different value to update',422);   
```


## Part 15:
---
Skipped this part because of using not the same laravel version, my error handling files were a bit different.

## Part 16:
---
Route-model binding explained.

In some cases like buyers we can't use route-model binding as usual because the query has a restriction that each buyer has to have at least one transaction to be called a buyer. In buyerController we have this:
```
public function show($id)
    {
        $buyer = Buyer::has('transactions')->findOrFail($id);

        return $this->showOne($buyer);
    }
 ```
 let's change the id to an instance of Buyer model as usual and comment out the first line.

 ```
 public function show(User $user)
    {
       // $buyer = Buyer::has('transactions')->findOrFail($id); //

        return $this->showOne($buyer);
    }

```
If we do it this way, the show method will show all users as buyers because we commented out the restriction.

In order to deal with this we need to apply that restriction automatically to all buyer queries we use in the app. To make that restriction we use something called __Global Scopes__.

Lets make a directory under App for all scopes and inside it we make BuyersScope.php which is a traditional php file.

We implement a class called Scope that comes with laravel package. Here is what we need to make as the structure of BuyerScope.php file:
```
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;

class BuyerScope implements Scope
{

}
```
The first thing we need to do is to define a method called apply and need to inject Builder and Model instances in it, also import them. like this:
```
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class BuyerScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->has('transactions');
    }
}
```
Now we need to tell show method in the model about this scope like this: (inside Buyer.php model)

```
?php

namespace App;

use App\Transaction;
use App\Scopes\BuyerScope;

class Buyer extends User
{
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BuyerScope);

    }

    protected $table = 'users';

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
```

## Part 17:
---

Soft deleting:
Laravel has a very nice feature called soft deleting that doesn't actually delete the record entirely from the database but it removes them from the app. Also it adds deleted_at timestamp to the database. In order for this to do we need to add softdeletes column to database tables like this:
```
$table->softDeletes();
```
We don't need to add this for pivot tables and passwords table.

In order for this to work we need to add a trait inside all models we want to use softDelete for:
```
use SoftDeletes;
```
Also we need to import it on the top:
```
use Illuminate\Database\Eloquent\SoftDeletes;
```
Nex thing is to add this after importing the trait:
```
protected $dates = ['deleted_at'];
```
Now we repeat this for all models except Buyer and Seller because they are subsets of users.

The soft delete functionality works now. Anytime we delete a record the deleted_at value will be saved in the database.

### Part 18:
---
For some methods in category controller we can't use route-model binding in a natural way. we need to do a trick like this:

First we delete the categoryCocntroller we had and Using the following artisan code we can make a new one:

```
 php artisan make:controller Category/CategoryController -r -m Category
 ```
 Writing artisan like this will use the Category model and inject it directly in all methods of the controller instead of id.

 As in the routes we said we dont want create and edit for categories we delete their methods in the controller.

Then we car write the index to show all categories but we need to change the header like this:
```
<?php

namespace App\Http\Controllers\Category;

use App\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class CategoryController extends ApiController
{

}
```

Here is the index method:

```
public function index()
    {
        $categories = Category::all();
        return $this->showAll($categories);
    }    
 ```
 Now it shows all categories as expcted.
 Other methods are easy justy check the source code.
 For Updating there is a new method called __fill()__. it is so similar to update except that it does the validation itself and waits for save method and if the data doesn't exist it returns false but update will change data right away.

__Intersect method:__  It removes items from the originall collection which don't exist in the passed collection. example: [here](https://laravel.com/docs/5.6/collections#method-intersect)

Here is how we use it for update:
```
    public function update(Request $request, Category $category)
    {
        $category->fill($request->intersect([
            'name',
            'description'
        ]));
    }
```

Actually intersect() is deprecated and we need to replacew it will only() like this:
```
	$category->fill($request->only('name','description'));
```
It means that we make sure users only pass name and description and not anything else (anything except these 2 will be deleted by laravel)

__isDirty()__ is a useful method saying if the values have been changed or not. for example in updating category we can say:

```
    if (!$category->isDirty()) {
        return $this->errorResponse('You need to pass new data!', 422);
    }
```
we could actually use __isClean()__ method which means not dirty.

### Part 19:
---
We have to make explicit route model binding again for Products as we did for Categories.
```
 php artisan make:controller Product/ProductsController -r -m Product`
 ```
Because according to rotues.php we just need index and show, we delete all other methods scaffolding code inside the productsController.

### Part 20:
---
We do exactly the same thing we did for Category and Product in part 18, 19 on Transaction controller.

### Part 21:
---
In this lesson we are going to learn how to use complex controllers that use multiple resources.

We want to return the category of a specific transaction. We need to make a new controller like this:
```
php artisan make:controller Transaction/TransactionCategoryController -r -m Transaction
```
for this controller we only need index method. so we erase all other methods inside the controller.

Then we need to add the route for it:
```
Route::resource('transactions.categories', 'Transaction\TransactionCategoryController', ['only' => 'index']);
```
Now we can write the index method like this:

```
    public function index(Transaction $transaction)
    {
        $categories = $transaction->product->categories;

        return $this->showAll($categories);
    }
 ```

Similarly we are going to make a new complex controller that lets us return the seller of a transaction. Since there is no relationship between seller and transaction directly we need to use the relationship netween product and seller and product and transaction.

again we make a new controller :
```
php artisan make:controller Transaction/TransactionSellerController -r -m Transaction
```

### Part 22:
---
Now we are going to make a controller that returns buyers in a transaction.
First we make the controller:
```
php artisan make:controller Buyer/BuyerTransactionConroller -r -m Buyer
````

Next, we will add a route for it:
```
Route::resource('buyers.transactions', 'Buyer\BuyerTransactionController', ['only' => 'index']);
```
And now we can retrieve data from the index method in the new controller.
Again simply like the previous episode we write index method and when we check the website like this:
```
laravel-app-api.test/buyers/234/transactions
```
It shows the transactions done by buyer with id of 234

Now its time to find the products that a buyer buys. We can access them through transactions of the buyer as we made before, but the problem is because the relationship of buyer with Transaction is many to many, laravel returns a collection of transactions and not an instance of it. So we need to go through all trnasactions and see the products of each.

But laravel has a way to deal with it.
The process of making the route and controller is similar, except the index method has to be written this way:
```
public function index(Buyer $buyer)
{
    $products = $buyer->transactions()->with('product')->get()->pluck();
    return $this->showAll($products)
}
```
__Eager Loading__: the technique of returning the list of products from transactions, when we practically it finds all products for the whole collection of transactions using with() is called Eager loading.

__Pluck()__ method will obtain only the products and not the whole collection. (very useful.)

Now we want to acquire the sellers of a transaction. This is so interesting because we have to find it through all of the other models. from transaction to products to seller. Also the sellers can be repeating. We need to make sure it returns only unique sellers.

Again we start from making the controller:
```
php artisan make:controller Buyer/BuyerSellerController -r -m Buyer
```
In order to write the index method it is so important that we doit this way:
```
public function index(Buyer $buyer)
{
    $sellers = $buyer->transactions()->with('product.seller')
    ->get()
    ->pluck('product.seller')
    ->values();
    return $this->showAll($sellers);
}
```
the product.seller part is called __Nested relationship__ that means since there is no relationship between buyer and seller, we can find it through the product.

Since we can't have duplicate in the results we use unique() method to return only unique id's.

But unique() will remove the duplicate, we don't want this. It leaves some empty instances in sellers and when we add more transactions in the future it can return null for some sellers, in order to prevent this we use values() method;

Finally we are going to retrieve categories of the bought products.
Controller:
```
php artisan make:controller Buyer/BuyerSellerController -r -m Buyer
```
Beacause the relationship between product and category is many to many the result would be collection within collection if we want to make it the same way as we did for others.
We can use a method called __collapse()__ that can show a list with all unique instances inside it.

Also we still need to use unique and values methods.

### Part 23:
---
Now we want to make a complex controller that returns the list of products for an specific category.
first we make the controller:
```
Terminal: php artisan make:controller Category/CategoryProductController -r -m Category
```
Again the same way we did it several times for other nested relationships we follow the same steps to acquire the list of products of each category (Don't forget that there is a direct relationship between category and product)

Again we want to have the list of sellers of a specific category.

It is again All similar as before. we must use eager loading as we did before.

Next we are going to find the list of transactions for each category.
* this is an interesting case because in this case some of the categories may don't have any transactions. But before we knew for example a transaction has a seller or a buyer.

So, we want to deal with the products that have already had at least one transaction.
In order to make this work, all the steps are the same except the index method that we used a new method called __whereHas()__. It simply returns only the products that have at lest one transaction.
here is the index method:
```
public function index(Category $category)
{
    $transactions = $category->products()
    ->whereHas('transactions')
    ->with('transactions')
    ->get()
    ->pluck('transactions')
    ->collapse();

    return $this->showAll($transactions);
}
```
Next is time to find buyers list from the category. This is a bit more complex that others controllers.

Let's see how it works.

Again all steps are the same except the index method which is like this:
```
public function index(Category $category)
{
    $buyers = $category->products()
    ->whereHas('transactions')
    ->with('transactions.buyer')
    ->get()
    ->pluck('transactions')
    ->collapse()
    ->pluck('buyer')
    ->unique('id')
    ->values();

    return $this->showAll($buyers);
}
```
In order to be able to get the list of all buyers from transactions.buyers we need to convert the list of collections into one list using collapse(), then from collapsed list which will be a list of all trasactions we pluck again to find the unique buyers.
The last thing here is, when we want to see the the list of products in a specific category, we see in the json list that there is something saying:
```
pivot: {
    "category_id": 1,
    "product_id": 15
}
```
How to remove this from the json? Its so easy we just need to go to Category and Product model and add this:
```
protected $hidden = [
    'pivot'
];
```

### Part 24:
---
In this part, we are going to do the same thig for seller. its similar to the previous parts.
_Important_: When we want to have store method for sellerProductsController, we can't inject seller because if someone wants to store a product for the first time, he/she is not still a seller, so we need to inject user instead of seller.

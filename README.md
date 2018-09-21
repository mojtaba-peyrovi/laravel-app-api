## Part 15:

Skipped this part because of using not the same laravel version, my error handling files were a bit different.

## Part 16:

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

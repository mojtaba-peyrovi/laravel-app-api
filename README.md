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

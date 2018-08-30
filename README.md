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

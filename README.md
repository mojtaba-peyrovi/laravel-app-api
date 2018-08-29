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

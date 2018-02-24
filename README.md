# Laravalid [![Build Status](https://travis-ci.org/bgultekin/laravalid.svg?branch=laravel4)](https://travis-ci.org/bgultekin/laravalid)
#### Laravel Validation For Client Side

This package makes validation rules defined in laravel work client-side by converting to html/js plugins such as jquery validation. It also allows to use laravel validation messages so you can show same messages for both sides.


### Table of contents
 - [Feature Overview](#feature-overview)
 - [Installation](#installation)
 - [Configuration](#configuration)
 - [Usage](#usage)
 - [Extending](#extending)
 - [Plugins and Supported Rules](#plugins-and-supported-rules)
 - [Known Issues](#knownissues)
 - [To Do](#todo)
 - [Changelog](#changelog)
 - [Licence](#licence)


### Feature Overview
- Multi-Plugin Support  *//For now, there is just one :)*
    - `Jquery Validation` 
- Extendible
- Laravel form builder based
- Validation rules can be set from controller
- Distinguishing between numeric input and string input
- User friendly input names
- Remote rules such as unique and exists

### Installation

Require `bllim/laravalid` in composer.json and run `composer update`.
```json
    {
        "require": {
            "laravel/framework": "4.2.*",
            ...
            "bllim/laravalid": "dev-laravel4"
        }
        ...
    }
```

Composer will download the package. After the package is downloaded, open `config/app.php` and add the service provider and alias as below:
```php
    'providers' => array(
        ...
            'Bllim\Laravalid\LaravalidServiceProvider',
    ),
```
```php
    'aliases' => array(
        ...
            'HTML'      => 'Illuminate\Support\Facades\HTML', // if not exists add for html too
            'Form'      => 'Bllim\Laravalid\Facade',
    ),
```

Also you need to publish configuration file and assets by running the following Artisan commands.
```bash
$ php artisan config:publish bllim/laravalid
$ php artisan asset:publish bllim/laravalid
```

### Configuration
After publishing configuration file, you can find it in `config/packages/bllim/laravalid` folder. Configuration parameters are as below:

| Parameter | Description | Values |
|-----------|-------------|--------|
| plugin | Choose plugin you want to use | See [Plugins and Supported Rules](#plugins-and-supported-rules) |
| useLaravelMessages | If it is true, laravel validation messages are used in client side otherwise messages of chosen plugin are used  | true/false | 
| route | Route name for remote validation | Any route name (default: laravalid) |

### Usage

The package uses laravel Form Builder to make validation rules work for both sides. Therefore you should use Form Builder. While opening form by using `Form::open` you can give `$rules` as second parameter:
```php
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::open(array('url' => 'foo/bar', 'method' => 'put'), $rules);
    Form::text('name');
    Form::text('email');
    Form::text('birthdate');
    Form::close(); // don't forget to close form, it reset validation rules
```
Also if you don't want to struggle with `$rules` at view files, you can set it in `Controller` or route by using `Form::setValidation`. This sets rules for first `Form::open`
```php
    // in controller or route
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::setValidation($rules);
    
    // in view
    Form::open(array('url' => 'foo/bar', 'method' => 'put'), $rules);
    // some form inputs
    Form::close();
```
For rules which is related to input type in laravel (such as `max`, `min`), the package looks for other given rules to understand which type is input. If you give integer or numeric as rule with `max`, `min` rules, the package assume input is numeric and convert to `data-rule-max` instead of `data-rule-maxlength`.
```php
    $rules = ['age' => 'numeric|max'];
```
The converter assume input is `string` by default. File type is also supported.

**Validation Messages**

Converter uses validation messages of laravel (`app/lang/en/validation.php`) by default for client-side too. If you want to use jquery validation messages, you can set `useLaravelMessages`, false in config file of package which you copied to your config dir. 

#### Plugins
**Jquery Validation**
While using Jquery Validation as html/js validation plugin, you should include `jquery.validate.laravalid.js` in your views, too. After assets published, it will be copied to your public folder. The last thing you should do at client side is initializing jquery validation plugin as below:
```html
<script type="text/javascript">
$('form').validate({onkeyup: false}); //while using remote validation, remember to set onkeyup false
</script>
```


#### Example
Controller/Route side
```php
class UserController extends Controller {
    
    static $createValidations = ['name' => 'required|max:255', 'username' => 'required|regex:/^[a-z\-]*$/|max:20', 'email' => 'required|email', 'age' => 'numeric'];

    public function getCreate()
    {
        Form::setValidation(static::$createValidations);
        return View::make('user.create');
    }

    public function postCreate()
    {
        $inputs = Input::only(array_keys(static::$createValidations));
        $validator = Validator::make($inputs, static::$createValidations);

        if ($validator->fails())
        {
            // actually withErrors is not really necessary because we already show errors at client side for normal users
            return Redirect::back()->withErrors($validator);
        }

        // try to create user

        return Redirect::back()->with('success', 'User is created successfully');
    }
}
```
View side
```html
<!DOCTYPE html>
<html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Laravalid</title>
    </head>
    <body>
    
        {{ Form::open('url'=>'create', 'method'=>'post') }}
        {{ Form::text('name') }}
        {{ Form::text('username') }}
        {{ Form::email('email') }}
        {{ Form::number('age') }}
        {{ Form::close() }}

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
        <script src="{{ asset('packages/bllim/laravalid/jquery.validate.laravalid.js') }}"></script>
        <script type="text/javascript">
        $('form').validate({onkeyup: false});
        </script>
    </body>
</html>
```
### Extending
There are two ways to extend package with your own rules. 
First, you can extend current converter plugin dynamically like below:
```php
Form::converter()->rule()->extend('someotherrule', function($parsedRule, $attribute, $type){
    // some code
    return ['data-rule-someotherrule' => 'blablabla'];
});
Form::converter()->message()->extend('someotherrule', function($parsedRule, $attribute, $type){
    // some code
    return ['data-message-someotherrule' => 'Some other message'];
});
Form::converter()->route()->extend('someotherrule', function($name, $parameters){
    // some code
    return ['valid' => false, 'messages' => 'Seriously dude, what kind of input is this?'];
});
```

Second, you can create your own converter (which extends `Base\Converter` or any current plugin converter) in `Bllim\Laravalid\Converter\` namespace and change plugin configuration in config file with your own plugin name.

> **Note:** If you are creating a converter for some existed html/js plugin please create it in `converter` folder and send a pull-request.

### Plugins and Supported Rules
**Jquery Validation**
To use Jquery Validation, change plugin to `JqueryValidation` in config file and import `jquery`, `jquery-validation` and **jquery.validate.laravalid.js** in views.


| Rules          | Jquery Validation |
| ---------------|:----------------:|
| Accepted  | - |
| Active URL  | `+` |
| After (Date)  | `+` |
| Alpha  | `+` |
| Alpha Dash  | - |
| Alpha Numeric  | `+` |
| Array  | - |
| Before (Date)  | `+` |
| Between  | `+` |
| Boolean  | - |
| Confirmed  | - |
| Date  | `+` |
| Date Format  | - |
| Different  | `+` |
| Digits  | - |
| Digits Between  | - |
| E-Mail  | `+` |
| Exists (Database)  | `+` |
| Image (File)  | `+` |
| In  | - |
| Integer  | `+` |
| IP Address  | `+` |
| Max  | `+` |
| MIME Types  | `+` |
| Min  | `+` |
| Not In  | - |
| Numeric  | `+` |
| Regular Expression  | `+` |
| Required  | `+` |
| Required If  | - |
| Required With  | `+` |
| Required With All  | - |
| Required Without  | `+` |
| Required Without All  | - |
| Same  | `+` |
| Size  | - |
| String  | - |
| Timezone  | - |
| Unique (Database)  | `+` |
| URL  | `+` |

> **Note:** It is easy to add some rules. Please check `Rule` class of related converter.

### Contribution
You can fork and contribute to development of the package. All pull requests is welcome.

**Conversion Logic**
Package converts rules by using converters (in `src/Bllim/Laravalid/converter`). It uses `Converter` class of chosen plugin which extends `Converter/Base/Converter` class. 
You can look at existed methods and plugins to understand how it works. Explanation will be ready, soon.

### Known issues
- Some rules are not supported for now

### TODO
- Support unsupported rules
- Improve doc
- Comment code

### Changelog
**v1.0**
- Initial commit

### License
Licensed under the MIT License

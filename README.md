# DB JSON Field for Silverstripe 6

### Requirements
* **silverstripe/framework** ~6.0

### Installation
* Add the following code to the `require` key in `composer.json` file:
```bash
composer require slonline/jsonfield
```

### Usage

#### Datatype
```
$db = [
    ...
    'MyJSON' => DBJSONText::class,
];
```
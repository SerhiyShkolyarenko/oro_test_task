To run these bundles.
1. Copy them to lib/ directory of your Symfony application.
2. Update autoload section of your composer.json file like this:
```
   "autoload": {
        "psr-4": {
            "App\\": "src/",
            "Serhiy\\ChainCommandBundle\\": "lib/ChainCommandBundle/src/",
            "Serhiy\\BarHiBundle\\": "lib/BarHiBundle/src/",
            "Serhiy\\FooHelloBundle\\": "lib/FooHelloBundle/src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests/",
            "Serhiy\\ChainCommandBundle\\Tests\\": "lib/ChainCommandBundle/tests/"
        }
    },
```
3. Update your config/bundles.php file like this:
```
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Serhiy\ChainCommandBundle\ChainCommandBundle::class => ['all' => true],
    Serhiy\BarHiBundle\BarHiBundle::class => ['all' => true],
    Serhiy\FooHelloBundle\FooHelloBundle::class => ['all' => true],
];
```

4. Update `lib/ChainCommandBundle/src/Resources/config/services.xml` file with console commands you want to chain.
5. Run any CLI command and check file var/log/{env}-console.log
6. Run tests: `vendor/bin/phpunit -c lib/ChainCommandBundle/phpunit.xml`
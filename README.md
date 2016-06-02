# sfphp
SimpleFramework(for)PHP
```
{
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/sincco/sfphp"
      }
    ],
    "require": {
        "sincco/sfphp": "dev-master",
        "desarrolla2/cache":  "~2.0",
        "twig/twig":  "~1.0"
    }
}
```

### CRUD

```
$model->empresas()
    ->where( 'estatus', 'Activa' )
    ->where( 'empresa', '01' )
    ->join( 'usuariosEmpresas usr', 'usr.empresa = maintable.empresa' )
    ->order( 'razonSocial' );
Debug::log($model->getCollection());
```
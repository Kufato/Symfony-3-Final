# Create the project with composer
composer create-project symfony/skeleton:"7.4.*" module_08

# Add the dependances for the project
composer require symfony/maker-bundle --dev
composer require symfony/orm-pack
composer require symfony/security-bundle
composer require symfony/form
composer require twig/twig
composer require symfony/twig-bundle
composer require symfony/asset
composer require symfony/validator 

# Exercice 00
- Create the Post entity 
    |--> php bin/console make:entity

# Exercice 01
- Create the User --> php bin/console make:user

- Configure the security --> config/packages/security.yaml

- Create the login handler --> src/Security/LoginSuccessHandler.php & src/Security/LoginFailureHandler.php

- Create the PostType (the form for the creation of a Post) --> php bin/console make:form PostType Post

- Create the controllers -->    php bin/console make:controller UserController
                                php bin/console make:controller PostController

- Create the templates --> templates/user/login.html.twig & templates/post/index.html.twig

- Migrate data -->  php bin/console doctrine:database:create 
                    php bin/console make:migration php
                    bin/console doctrine:migrations:migrate

- Create custom command to add new users -->    php bin/console make:command app:create-user
                                                src/Command/CreateUserCommand.php

- Add new users --> php bin/console app:create-user

# Exercice 02
- Modify the defaultAction function in the PostController to retrieve all the post and send them to the template

- Modify the Post entity to set the unique title

- Modify the newAction in the PostController to retrieve errors from the form

- Update the index.html.twig and the base.html.twig file to display all the post
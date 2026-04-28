# Create the project with composer
composer create-project symfony/skeleton:"7.4.*" module_08

# Add the dependances for the project
composer require symfony/maker-bundle --dev
composer require symfony/orm-pack

# Exercice 00
Create the entity Post with this command --> php bin/console make:entity
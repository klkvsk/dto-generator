all: gen-php7.4 gen-php8.0 gen-php8.1

gen-php7.4: dto.schema.php
	OUTPUT_DIR=php7.4 php8.1 ../bin/dto-gen --target 7.4

gen-php8.0: dto.schema.php
	OUTPUT_DIR=php8.0 php8.1 ../bin/dto-gen --target 8.0

gen-php8.1: dto.schema.php
	OUTPUT_DIR=php8.1 php8.1 ../bin/dto-gen --target 8.1

clean:
	rm -r php*

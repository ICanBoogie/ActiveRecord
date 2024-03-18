# Data Mapper

```php
<?php

interface Converter
{
    public function from(mixed $value): mixed
    public function to(mixed $value): mixed
}

```

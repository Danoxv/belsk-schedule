# Утилиты для работы с расписанием колледжа
Утилиты для манипуляции с расписанием Белгородского Строительного Колледжа: http://www.belsk.ru/

Доступные функции:
* Представление информации о занятиях для выбранной группы в удобном виде, с возможностью скачать как PDF.

## Требования
* PHP >= 7.4
    - С модулями: ext-curl, ext-dom
* Apache 2.4

## Установка
Распаковать файлы проекта и запустить:
```
composer install
```
## Архитектура
Архитектура приложения построена с использованием паттерна [Единая точка входа](https://ru.wikipedia.org/wiki/Единая_точка_входа_(шаблон_проектирования)) (Single entry point), на основе каркаса [NewEXE/single-entry-point-php](https://github.com/NewEXE/single-entry-point-php).

### Как создать страницу
1. Создать PHP-файл страницы в директории `src/pages` (допускается создание в субдиректории)
2. Добавить роут в `src/Config/routes.php`

### Как добавить и запустить консольный (CLI) скрипт
#### Создать:
1. Создать PHP-файл скрипта в директории `src/scripts` (допускается создание в субдиректории)
#### Запустить:
К примеру, скрипт находится по пути `src/scripts/sub-dir/script.php`:
```
php public/index.php sub-dir/script.php
```
(расширение `.php` можно опустить)

### Как добавить свойство в конфиг приложения
1. Добавить ключ со значением в `src/Config/app.php`
2. Добавить свойство в класс `Src\Config\AppConfig`

## Утилиты
### Обновить актуальный список всех групп для страницы `select-schedule-file.php`:
```
php public/index.php group-list/generate.php
```
Получает все группы с Excel-файлов техникума и с файлов примеров (`public/samples`).

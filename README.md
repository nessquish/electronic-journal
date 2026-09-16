# Электронный журнал группы

Веб-приложение для учёта студентов, групп и оценок. Написано на PHP с использованием PDO, данные хранятся в базе SQLite.

## ✨ Возможности

- Просмотр списка студентов с названием группы (LEFT JOIN)
- Добавление нового студента через форму
- Редактирование данных студента
- Удаление студента с подтверждением
- Статистика по группам (GROUP BY)
- Защита от SQL-инъекций (prepared statements)
- Защита от XSS (`htmlspecialchars`)
- Красивый переливающийся пастельный дизайн

## 🛠️ Стек технологий

- **PHP 7.0+** (рекомендуется 8.0 и выше)
- **SQLite** (база данных в одном файле)
- **PDO** для подключения к БД
- **DBeaver** для управления базой
- **VSCodium** как редактор кода

## 📋 Требования

- **PHP 7.0 или новее** (проверено на 8.5)
- Расширения PHP:
  - `pdo_sqlite`
  - `sqlite3`
- Любая ОС: Windows, macOS, Linux

Проект использует только базовые возможности PHP и PDO, поэтому работает на всех версиях PHP 7.x и 8.x. Никакие «новые» синтаксические конструкции (стрелочные функции, `match`, nullsafe-оператор и т.п.) не применяются.

## 🚀 Как запустить у себя

### 1. Установить PHP

#### Windows

1. Откройте https://windows.php.net/download/
2. Скачайте **VS17 x64 Thread Safe (Zip)** для вашей версии PHP
3. Распакуйте архив в `C:\php` так, чтобы файл `php.exe` лежал прямо там
4. Добавьте `C:\php` в переменную среды **Path**:
   - `Win + R` → `sysdm.cpl` → вкладка **Дополнительно** → **Переменные среды**
   - В разделе **Переменные среды пользователя** выберите `Path` → **Изменить** → **Создать** → введите `C:\php` → OK
5. В `C:\php` скопируйте `php.ini-development` в `php.ini`
6. Откройте `php.ini` и найдите строки:
   ```
   ;extension=pdo_sqlite
   ;extension=sqlite3
   ```
   Удалите точку с запятой в начале каждой:
   ```
   extension=pdo_sqlite
   extension=sqlite3
   ```
7. Сохраните и **перезапустите терминал**
8. Проверьте:
   ```bash
   php -v
   php -m | findstr sqlite
   ```
   Должны увидеть версию PHP и две строки: `pdo_sqlite`, `sqlite3`.

#### macOS

```bash
brew install php
```
Расширения `pdo_sqlite` и `sqlite3` идут в комплекте.

#### Linux (Debian/Ubuntu)

```bash
sudo apt update
sudo apt install php php-sqlite3
```

### 2. Скачать проект

Через Git:
```bash
git clone https://github.com/nessquish/electronic-journal.git
cd electronic-journal
```

Или через **Code → Download ZIP** на странице репозитория и распаковать.

### 3. Запустить сервер

Из папки проекта:

```bash
php -S localhost:8000
```

Терминал выведет:
```
PHP 8.5.10 Development Server (http://localhost:8000) started
```

> ⚠️ Терминал **не закрывайте** — пока он работает, живёт сайт.
> Остановить сервер — `Ctrl+C`.

### 4. Открыть в браузере

Перейдите по адресу:

```
http://localhost:8000/
```

Готово — вы увидите страницу «Электронный журнал группы».

## 📁 Структура проекта

```
electronic-journal/
├── config.php       # путь к базе данных
├── db.php           # функция getPDO() — подключение через PDO
├── index.php        # главная: список + добавление + удаление + статистика
├── edit.php         # редактирование студента
├── school.sqlite    # база данных SQLite
└── README.md        # этот файл
```

## 🗄️ Структура базы данных

| Таблица    | Назначение                           |
|------------|--------------------------------------|
| `groups`   | Учебные группы                       |
| `teachers` | Преподаватели                        |
| `subjects` | Дисциплины (со ссылкой на teacher)   |
| `students` | Студенты (со ссылкой на group)       |
| `grades`   | Оценки (студент + дисциплина + балл) |

Связь `students ↔ subjects` реализована через промежуточную таблицу `grades` (Many-to-Many).

### SQL-схема

```sql
CREATE TABLE groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE teachers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE
);

CREATE TABLE subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    teacher_id INTEGER,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    group_id INTEGER,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL
);

CREATE TABLE grades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER,
    subject_id INTEGER,
    grade INTEGER NOT NULL CHECK (grade BETWEEN 2 AND 5),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);
```

## 🔐 Безопасность

- Все SQL-запросы с параметрами выполняются через `prepare()` + `execute([...])`
- Все выводимые данные оборачиваются в `htmlspecialchars()`
- Ошибки подключения обрабатываются через `try-catch` с `PDOException`
- После POST-запроса выполняется редирект (`header('Location: ...')`) — защита от повторной отправки формы

## 🆘 Решение проблем

### `php не является внутренней или внешней командой`

PHP не добавлен в PATH или терминал не перезапущен.
- Проверьте, что `C:\php` есть в переменной **Path**.
- Закройте **все** терминалы и откройте новый.
- Если не помогает — перезапустите компьютер.

### `could not find driver`

Не включены расширения SQLite. Откройте `php.ini` и раскомментируйте:
```
extension=pdo_sqlite
extension=sqlite3
```
Затем перезапустите сервер.

### `unable to open database file`

Файл `school.sqlite` не найден или лежит не рядом с PHP-файлами. Убедитесь, что структура папок совпадает:
```
journal/
├── config.php
├── db.php
├── index.php
├── edit.php
└── school.sqlite   ← здесь
```

### Белая страница или ошибка 500

Включите вывод ошибок: в самое начало `config.php` добавьте:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
Перезагрузите страницу — увидите текст ошибки.

### Порт 8000 занят

Запустите сервер на другом порту:
```bash
php -S localhost:8080
```
И откройте http://localhost:8080/

### `Fatal error: Uncaught PDOException`

Скорее всего, проблема с подключением. Проверьте:
- файл `school.sqlite` существует;
- путь в `config.php` совпадает с реальным положением файла;
- расширения SQLite включены (`php -m | findstr sqlite`).

## 📜 Лицензия

Учебный проект. Свободно используйте в образовательных целях.

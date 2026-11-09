<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Reportable.php';

/**
 * Общая логика всех отчётов: выполнение запроса и подготовка результата.
 * Наследники задают только название, колонки и запрос.
 */
abstract class Report implements Reportable
{
    // Доступно наследникам
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    abstract protected function getTitle(): string;

    /**
     * Колонки отчёта: поле запроса => заголовок.
     */
    abstract protected function getColumns(): array;

    /**
     * Запрос отчёта: массив из SQL и параметров.
     */
    abstract protected function getQuery(): array;

    public function generate(): array
    {
        // Берём запрос наследника
        [$sql, $params] = $this->getQuery();
        // Выполняем запрос
        $rows = $this->db->query($sql, $params)->fetchAll();

        // Единый формат результата
        return [
            'title' => $this->getTitle(),
            'columns' => $this->getColumns(),
            'rows' => array_map([$this, 'formatRow'], $rows),
        ];
    }

    /**
     * Единый формат среднего балла для всех отчётов.
     */
    private function formatRow(array $row): array
    {
        // Формат нужен только среднему баллу
        if (array_key_exists('avg_grade', $row)) {
            $row['avg_grade'] = $row['avg_grade'] === null ? '—' : number_format($row['avg_grade'], 2);
        }
        return $row;
    }
}

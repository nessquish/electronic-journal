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
     * Отдаёт отчёт браузеру файлом CSV.
     */
    public function toCsv(): void
    {
        // Общая логика: любой отчёт собирается одинаково
        $data = $this->generate();
        $fileName = rawurlencode($data['title']) . '.csv';

        // Сообщаем браузеру, что это файл
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"report.csv\"; filename*=UTF-8''$fileName");

        // Пишем прямо в ответ браузеру
        $out = fopen('php://output', 'w');
        // Метка UTF-8, чтобы Excel показал кириллицу
        fwrite($out, "\xEF\xBB\xBF");
        // Заголовки колонок
        fputcsv($out, array_values($data['columns']), ';', '"', '');

        // Строки в порядке колонок
        foreach ($data['rows'] as $row) {
            $line = [];
            foreach (array_keys($data['columns']) as $key) {
                $line[] = $row[$key];
            }
            fputcsv($out, $line, ';', '"', '');
        }
        fclose($out);
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

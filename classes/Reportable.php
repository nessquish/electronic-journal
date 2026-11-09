<?php

/**
 * Общий контракт: всё, что умеет формировать отчёт.
 */
interface Reportable
{
    /**
     * Возвращает массив с ключами title, columns и rows.
     */
    public function generate(): array;
}

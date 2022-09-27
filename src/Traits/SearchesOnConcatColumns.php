<?php

namespace Shepp\NovaConcatSearch\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SearchesOnConcatColumns
{
    /**
     * Apply the search query to the query.
     *
     * Overrides @see \Laravel\Nova\PerformsQueries::applySearch
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    protected static function applySearch($query, $search)
    {
        return $query->where(function (Builder $query) use ($search) {
            $model = $query->getModel();

            $connectionType = $model->getConnection()->getDriverName();

            $canSearchPrimaryKey = ctype_digit($search) &&
                in_array($model->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= static::maxPrimaryKeySize()) &&
                in_array($model->getKeyName(), static::$search);

            if ($canSearchPrimaryKey) {
                $query->orWhere($model->getQualifiedKeyName(), $search);
            }

            $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

            foreach (static::searchableColumns() as $column) {
                if (is_array($column)) {
                    static::queryConcatColumns(
                        $query,
                        $column,
                        $likeOperator,
                        $search
                    );
                } else {
                    $query->orWhere(
                        $model->qualifyColumn($column),
                        $likeOperator,
                        static::searchableKeyword($column, $search)
                    );
                }
            }
        });
    }

    /**
     * @param Builder $query
     * @param array $columns
     * @param string $likeOperator
     * @param string $search
     * @return void
     */
    protected static function queryConcatColumns(
        Builder $query,
        array $columns,
        string $likeOperator,
        string $search
    ): void {
        $model = $query->getModel();

        $qualifiedCols = implode(
            ", ' ', ",
            array_map(fn ($column) => $model->qualifyColumn($column), $columns)
        );

        $concat = "CONCAT(" . $qualifiedCols . ")";
        $where = $concat . ' ' . $likeOperator . ' ?';
        $keyword = static::searchableKeyword('', $search);

        $query->orWhereRaw($where, [$keyword]);
    }
}

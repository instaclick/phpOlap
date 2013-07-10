<?php

/*
* This file is part of phpOlap.
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace phpOlap\Layout;

use phpOlap\Metadata\ResultSetInterface;

/**
 * JsonLayout output. Does not support multiple columns.
 */
class JsonLayout implements LayoutInterface
{
    /**
     * @var phpOlap\Metadata\ResultSetInterface
     */
    protected $resultSet;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $rows;

    /**
     * {@inheritDoc}
     */
    public function __construct(ResultSetInterface $resultSet)
    {
        $this->resultSet = $resultSet;
    }

    /**
     * {@inheritDoc}
     */
    public function generate()
    {
        return json_encode($this->buildResultList());
    }

    /**
     * Retrieve columns, build the structure if necessary.
     *
     * @return array Strings with unique name
     */
    private function getColumns()
    {
        if ( ! isset($this->columns)) {
            $this->columns = array();
            $rawColumns    = $this->resultSet->getColAxisSet();

            foreach($rawColumns as $rawColumn) {
                $this->columns[] = $this->friendlyName($rawColumn[0]->getMemberUniqueName());
            }
        }

        return $this->columns;
    }

    /**
     * Retrieve rows, build the structure if necessary.
     *
     * @return array Array with name and caption entries
     */
    private function getRows()
    {
        if ( ! isset($this->rows)) {
            $this->rows = array();
            $rawRows    = $this->resultSet->getRowAxisSet();

            foreach($rawRows as $rawRow) {
                $row = array();

                foreach($rawRow as $column) {
                    $row[] = array(
                        "name"    => $this->friendlyName($column->getLevelUniqueName()),
                        "caption" => $column->getMemberCaption()
                    );
                }

                $this->rows[] = $row;
            }
        }

        return $this->rows;
    }

    /**
     * Build an array with all data from result set.
     *
     * @return array
     */
    private function buildResultList()
    {
        $resultList = array();

        for ($rowIndex = 0; $rowIndex < count($this->getRows()); $rowIndex++) {
            $resultList[] = $this->buildResult($rowIndex);
        }

        return $resultList;
    }

    /**
     * Build a single result row from result set.
     *
     * @param integer $rowIndex
     *
     * @return array
     */
    private function buildResult($rowIndex)
    {
        $result           = array();
        $rows             = $this->getRows();
        $columns          = $this->getColumns();
        $rowRelativeIndex = count($columns) * $rowIndex;
        $dataSet          = $this->resultSet->getDataSet();

        foreach($rows[$rowIndex] as $column) {
            $columnName = $this->friendlyName($column["name"]);
            $result[$columnName] = $column["caption"];
        }

        for ($columnIndex = 0; $columnIndex < count($columns); $columnIndex++) {
            $result[$columns[$columnIndex]] = isset($dataSet[$rowRelativeIndex + $columnIndex])
                                            ? $dataSet[$rowRelativeIndex + $columnIndex]->getValue()
                                            : null;
        }

        return $result;
    }

    /**
     * Transform the name from result set into an friendlier name for Javascript.
     *
     * @param string $name
     *
     * @return string
     */
    private function friendlyName($name) {
        $name = str_replace(array('[', ']', '*'), '', $name);
        $name = strtolower($name);
        $name = implode("_", explode(".", $name));

        return $name;
    }
}

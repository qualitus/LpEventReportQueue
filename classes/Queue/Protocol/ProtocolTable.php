<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace QU\LERQ\Queue\Protocol;

use DateTimeImmutable;
use DateTimeInterface;
use ilCSVWriter;
use ilDateTime;
use ilExcel;
use ilTableFilterItem;
use QU\LERQ\Queue\Protocol\Table\Formatter\EventTypeFormatter;
use ILIAS\DI\UIServices;
use ILIAS\UI\Component\Component;
use ilTable2GUI;
use QU\LERQ\Queue\Protocol\Table\Formatter\JsonAttributeFormatter;
use QU\LERQ\Queue\Protocol\Table\Formatter\JsonDocumentFormatter;
use QU\LERQ\UI\Table\Base;
use ilLpEventReportQueueConfigGUI;
use ilLpEventReportQueuePlugin;
use QU\LERQ\UI\Table\Formatter\TimestampFormatter;

/**
 * @extends Base<array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}>
 */
class ProtocolTable extends Base
{
    /** @var array<int, array> */
    private array $cachedColumnDefinition = [];
    private ilLpEventReportQueuePlugin $plugin;
    private UIServices $uiServices;
    /** @var list<Component> */
    private array $uiComponents = [];

    public function __construct(
        ilLpEventReportQueueConfigGUI $ctrlInstance,
        ilLpEventReportQueuePlugin $plugin,
        UIServices $uiServices,
        string $command
    ) {
        $this->plugin = $plugin;
        $this->uiServices = $uiServices;

        $this->setId('protocol');
        $this->setTitle($this->plugin->txt('queue_protocol'));

        $this->setFormName($this->getId());
        parent::__construct($ctrlInstance, $command);

        $this->setDefaultOrderDirection('DESC');
        $this->setDefaultOrderField('id');
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);

        $this->setFormAction($this->ctrl->getFormAction($ctrlInstance, $command));
        $this->setRowTemplate(
            'tpl.generic_table_row.html',
            'Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue'
        );

        $this->initFilter();
        $this->setDefaultFilterVisiblity(true);
        $this->setFilterCommand('applyProtocolFilter');
        $this->setResetCommand('resetProtocolFilter');

        $this->setExportFormats([self::EXPORT_CSV, self::EXPORT_EXCEL]);
    }

    public function addFilterItemByMetaType(
        string $id,
        int $type = self::FILTER_TEXT,
        bool $a_optional = false,
        string $caption = ""
    ): ?ilTableFilterItem {
        $item = parent::addFilterItemByMetaType($id, $type, $a_optional, $caption);

        $this->filter[$id] = $item->getValue();

        return $item;
    }

    public function initFilter(): void
    {
        $event_id = $this->addFilterItemByMetaType(
            'id',
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->plugin->txt('tbl_col_event_id')
        );
        $event_id->setSize(5);

        $timestamp = $this->addFilterItemByMetaType(
            'timestamp',
            ilTable2GUI::FILTER_DATE_RANGE,
            false,
            $this->plugin->txt('tbl_col_event_timestamp')
        );

        $subject_id = $this->addFilterItemByMetaType(
            'subject_id',
            ilTable2GUI::FILTER_TEXT,
            true,
            $this->plugin->txt('tbl_col_event_subject_id')
        );

        $subject_login = $this->addFilterItemByMetaType(
            'subject_login',
            ilTable2GUI::FILTER_TEXT,
            true,
            $this->plugin->txt('tbl_col_event_subject_title')
        );

        $object_id = $this->addFilterItemByMetaType(
            'object_id',
            ilTable2GUI::FILTER_TEXT,
            true,
            $this->plugin->txt('tbl_col_event_object_id')
        );

        $object_ref_id = $this->addFilterItemByMetaType(
            'object_ref_id',
            ilTable2GUI::FILTER_TEXT,
            true,
            $this->plugin->txt('tbl_col_event_object_ref_id')
        );

        $object_title = $this->addFilterItemByMetaType(
            'object_title',
            ilTable2GUI::FILTER_TEXT,
            true,
            $this->plugin->txt('tbl_col_event_object_title')
        );

        $query = $this->addFilterItemByMetaType(
            'query',
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->plugin->txt('tbl_filter_event_query')
        );
    }

    protected function getColumnDefinition(): array
    {
        if ($this->cachedColumnDefinition !== []) {
            return $this->cachedColumnDefinition;
        }

        $i = 0;

        $columns = [];

        $columns[++$i] = [
            'field' => 'id',
            'txt' => $this->plugin->txt('tbl_col_event_id'),
            'default' => true,
            'optional' => false,
            'sortable' => true,
            'width' => '5%',
        ];
        $columns[++$i] = [
            'field' => 'timestamp',
            'txt' => $this->plugin->txt('tbl_col_event_timestamp'),
            'default' => true,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'event_type',
            'txt' => $this->plugin->txt('tbl_col_event_type'),
            'default' => true,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'subject_id',
            'txt' => $this->plugin->txt('tbl_col_event_subject_id'),
            'default' => false,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'subject_title',
            'txt' => $this->plugin->txt('tbl_col_event_subject_title'),
            'default' => true,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'subject_email_addr',
            'txt' => $this->plugin->txt('tbl_col_event_subject_email_addr'),
            'default' => false,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'object_id',
            'txt' => $this->plugin->txt('tbl_col_event_object_id'),
            'default' => false,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'object_ref_id',
            'txt' => $this->plugin->txt('tbl_col_event_object_ref_id'),
            'default' => false,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'object_title',
            'txt' => $this->plugin->txt('tbl_col_event_object_title'),
            'default' => true,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'progress',
            'txt' => $this->plugin->txt('tbl_col_event_progress'),
            'default' => true,
            'optional' => true,
            'sortable' => true,
        ];
        $columns[++$i] = [
            'field' => 'actions',
            'txt' => $this->lng->txt('actions'),
            'default' => true,
            'optional' => false,
            'sortable' => false,
        ];

        $this->cachedColumnDefinition = $columns;

        return $this->cachedColumnDefinition;
    }

    protected function formatCellValue(string $column, array $row): string
    {
        if ('actions' === $column) {
            return $this->formatActionDropdown($column, $row);
        }
        if ('timestamp' === $column) {
            return (new TimestampFormatter())->format((int) $row[$column]);
        }
        if ('event_type' === $column) {
            return (new EventTypeFormatter('event', 'event_type'))->format($row);
        }
        if ('subject_id' === $column) {
            return (new JsonAttributeFormatter('usr_id'))->format($row['user_data']);
        }
        if ('subject_title' === $column) {
            return (new JsonAttributeFormatter('username'))->format($row['user_data']);
        }
        if ('subject_email_addr' === $column) {
            return (new JsonAttributeFormatter('email'))->format($row['user_data']);
        }
        if ('object_id' === $column) {
            return (new JsonAttributeFormatter('id'))->format($row['obj_data']);
        }
        if ('object_ref_id' === $column) {
            return (new JsonAttributeFormatter('ref_id'))->format($row['obj_data']);
        }
        if ('object_title' === $column) {
            return (new JsonAttributeFormatter('title'))->format($row['obj_data']);
        }
        if ('progress' === $column) {
            return $this->progress($row);
        }

        return parent::formatCellValue($column, $row);
    }

    protected function formatActionDropdown(string $column, array $row): string
    {
        $buttons = [];

        $json_sections = [
            'User' => $row['user_data'],
            'Object' => $row['obj_data'],
            'Membership' => $row['mem_data'],
        ];


        $modal = $this->uiServices->factory()
            ->modal()
            ->lightbox([$this->uiServices->factory()->modal()->lightboxTextPage(
                implode(
                    '',
                    [...[
                        $this->uiServices->renderer()->render(
                            $this->uiServices->factory()->panel()->standard(
                                'Common',
                                $this->uiServices->factory()->listing()->unordered([
                                    $this->plugin->txt('tbl_col_event_id') . ': ' . $row['id'],
                                    $this->plugin->txt('tbl_col_event_timestamp') . ': ' . (new TimestampFormatter())->format((int) $row['timestamp']),
                                    $this->plugin->txt('tbl_col_event_type') . ': ' . (new EventTypeFormatter('event', 'event_type'))->format($row),
                                ])
                            )
                        )
                    ], ...array_map(
                        function (string $value, string $header): string {
                            $content = (new JsonDocumentFormatter())->format($value);

                            return $this->uiServices->renderer()->render(
                                $this->uiServices->factory()->panel()->standard(
                                    $header,
                                    $this->uiServices->factory()->legacy($content)
                                )
                            );
                        },
                        array_filter($json_sections),
                        array_keys(array_filter($json_sections))
                    )]
                ),
                'JSON'
            )]);

        $buttons[] = $this->uiServices->factory()
            ->button()
            ->shy(
                $this->lng->txt('details'),
                '#'
            )
            ->withOnClick($modal->getShowSignal());

        if ([] === $buttons) {
            return '';
        }

        $actions = $this->uiServices->factory()
            ->dropdown()
            ->standard($buttons)
            ->withLabel($this->lng->txt('actions'));

        return $this->uiServices->renderer()->render([$actions, $modal]);
    }

    public function getHTML(): string
    {
        return parent::getHTML() . $this->uiServices->renderer()->render($this->uiComponents);
    }

    protected function fillHeaderExcel(ilExcel $a_excel, int &$a_row): void
    {
        $col = 0;
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_id'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_timestamp'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_type'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_subject_id'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_subject_title'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_subject_email_addr'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_object_id'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_object_ref_id'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_object_title'));
        $a_excel->setCell($a_row, $col++, $this->plugin->txt('tbl_col_event_progress'));
        $a_excel->setCell($a_row, $col++, 'user_data');
        $a_excel->setCell($a_row, $col++, 'obj_data');
        $a_excel->setCell($a_row, $col++, 'mem_data');
    }

    protected function fillRowExcel(ilExcel $a_excel, int &$a_row, array $a_set): void
    {
        $col = 0;
        $a_excel->setCell($a_row, $col++, $a_set['id']);
        $a_excel->setCell($a_row, $col++, new ilDateTime(
            is_numeric($a_set['timestamp']) ? (int) $a_set['timestamp'] : null,
            IL_CAL_UNIX
        ));
        $a_excel->setCell($a_row, $col++, $a_set['event']);
        $a_excel->setCell($a_row, $col++, $a_set['event_type']);
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('usr_id'))->format($a_set['user_data']));
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('username'))->format($a_set['user_data']));
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('email'))->format($a_set['user_data']));
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('id'))->format($a_set['obj_data']));
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('ref_id'))->format($a_set['obj_data']));
        $a_excel->setCell($a_row, $col++, (new JsonAttributeFormatter('title'))->format($a_set['obj_data']));
        $a_excel->setCell($a_row, $col++, $this->progress($a_set));
        $a_excel->setCell($a_row, $col++, $a_set['user_data']);
        $a_excel->setCell($a_row, $col++, $a_set['obj_data']);
        $a_excel->setCell($a_row, $col++, $a_set['mem_data']);
    }

    protected function fillHeaderCSV(ilCSVWriter $a_csv): void
    {
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_id'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_timestamp'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_type'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_subject_id'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_subject_title'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_subject_email_addr'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_object_id'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_object_ref_id'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_object_title'));
        $a_csv->addColumn($this->plugin->txt('tbl_col_event_progress'));
        $a_csv->addColumn('user_data');
        $a_csv->addColumn('obj_data');
        $a_csv->addColumn('mem_data');

        $a_csv->addRow();
    }

    protected function fillRowCSV(ilCSVWriter $a_csv, array $a_set): void
    {
        $a_csv->addColumn($a_set['id']);
        $a_csv->addColumn(
            is_numeric($a_set['timestamp']) ?
                (new DateTimeImmutable('@' . $a_set['timestamp']))->format(DateTimeInterface::ATOM) :
                ''
        );
        $a_csv->addColumn($a_set['event']);
        $a_csv->addColumn($a_set['event_type']);
        $a_csv->addColumn((new JsonAttributeFormatter('usr_id'))->format($a_set['user_data']));
        $a_csv->addColumn((new JsonAttributeFormatter('username'))->format($a_set['user_data']));
        $a_csv->addColumn((new JsonAttributeFormatter('email'))->format($a_set['user_data']));
        $a_csv->addColumn((new JsonAttributeFormatter('id'))->format($a_set['obj_data']));
        $a_csv->addColumn((new JsonAttributeFormatter('ref_id'))->format($a_set['obj_data']));
        $a_csv->addColumn((new JsonAttributeFormatter('title'))->format($a_set['obj_data']));
        $a_csv->addColumn($this->progress($a_set));
        $a_csv->addColumn($a_set['user_data']);
        $a_csv->addColumn($a_set['obj_data']);
        $a_csv->addColumn($a_set['mem_data']);

        $a_csv->addRow();
    }

    private function progress(array $row): string
    {
        $this->lng->loadLanguageModule('trac');
        return ($row['progress'] ?? false) ? $this->lng->txt('trac_' . $row['progress']) : '';
    }
}

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

namespace QU\LERQ\API;

use ILIAS\DI\UIServices;
use ilLpEventReportQueueConfigGUI;
use ilLpEventReportQueuePlugin;
use QU\LERQ\API\Table\Formatter\JsonDocumentFormatter;
use QU\LERQ\Model\RoutinesModel;
use QU\LERQ\UI\Table\Base;

/**
 * @extends Base<array{"name": string, "path": string, "active_overrides": RoutinesModel, "has_overrides": bool}>
 */
class ProviderTable extends Base
{
    /** @var array<int, array> */
    private array $cachedColumnDefinition = [];
    private ilLpEventReportQueuePlugin $plugin;
    private UIServices $uiServices;

    public function __construct(
        ilLpEventReportQueueConfigGUI $ctrlInstance,
        ilLpEventReportQueuePlugin $plugin,
        UIServices $uiServices,
        string $command
    ) {
        $this->plugin = $plugin;
        $this->uiServices = $uiServices;

        $this->setId('provider');
        $this->setTitle($this->plugin->txt('queue_providers'));

        $this->setFormName($this->getId());
        parent::__construct($ctrlInstance, $command);

        $this->setDefaultOrderDirection('name');
        $this->setDefaultOrderField('ASC');
        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);

        $this->setFormAction($this->ctrl->getFormAction($ctrlInstance, $command));
        $this->setRowTemplate(
            'tpl.generic_table_row.html',
            'Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue'
        );
    }

    protected function getColumnDefinition(): array
    {
        if ($this->cachedColumnDefinition !== []) {
            return $this->cachedColumnDefinition;
        }

        $i = 0;

        $columns = [
            ++$i => [
                'field' => 'name',
                'txt' => $this->lng->txt('title'),
                'default' => true,
                'optional' => false,
                'sortable' => true,
            ],
            ++$i => [
                'field' => 'path',
                'txt' => $this->lng->txt('path'),
                'default' => false,
                'optional' => true,
                'sortable' => true,
            ],
            ++$i => [
                'field' => 'has_overrides',
                'txt' => $this->plugin->txt('tbl_col_has_overrides'),
                'default' => true,
                'optional' => false,
                'sortable' => true,
            ],
            ++$i => [
                'field' => 'actions',
                'txt' => $this->lng->txt('actions'),
                'default' => true,
                'optional' => false,
                'sortable' => false,
            ],
        ];

        $this->cachedColumnDefinition = $columns;

        return $this->cachedColumnDefinition;
    }

    protected function formatCellValue(string $column, array $row): string
    {
        if ('actions' === $column) {
            return $this->formatActionDropdown($column, $row);
        }

        if ($column === 'has_overrides') {
            $status = $this->uiServices->factory()->symbol()->icon()->custom(
                'templates/default/images/icon_not_ok.svg',
                $this->lng->txt('no')
            );
            if ($row[$column]) {
                $status = $this->uiServices->factory()->symbol()->icon()->custom(
                    'templates/default/images/icon_ok.svg',
                    $this->lng->txt('yes')
                );
            }

            return $this->uiServices->renderer()->render($status);
        }

        return parent::formatCellValue($column, $row);
    }

    protected function formatActionDropdown(string $column, array $row): string
    {
        $buttons = [];

        $modal = $this->uiServices->factory()
            ->modal()
            ->lightbox([$this->uiServices->factory()->modal()->lightboxTextPage(
                implode('', array_map(static function (string $value): string {
                    return (new JsonDocumentFormatter())->format($value);
                }, array_filter([
                    $row['active_overrides'],
                ]))),
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
}

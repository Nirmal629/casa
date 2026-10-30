<?php

declare(strict_types=1);

namespace App\Helpers {

    use JsonSerializable;

    class Pagination implements JsonSerializable
    {
        public const int DEFAULT_LIMIT = 10;
        protected static int $globalDefaultLimit = self::DEFAULT_LIMIT;

        public int $total;
        public int $perPage;
        public int $currentPage;
        public int $totalPages;
        public int $offset;

        public function __construct(int $total, ?int $currentPage = null, ?int $perPage = null)
        {
            $this->total = max(0, $total);
            $this->perPage = ($perPage !== null && $perPage > 0) ? $perPage : static::$globalDefaultLimit;

            if ($currentPage === null) {
                $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            }

            $this->totalPages = $this->total > 0 ? (int) ceil($this->total / $this->perPage) : 1;
            $this->currentPage = max(1, min($currentPage, $this->totalPages));
            $this->offset = ($this->currentPage - 1) * $this->perPage;
        }

        public static function setDefaultLimit(int $limit): void
        {
            if ($limit > 0) {
                static::$globalDefaultLimit = $limit;
            }
        }

        public static function resetDefaultLimit(): void
        {
            static::$globalDefaultLimit = self::DEFAULT_LIMIT;
        }

        public static function getDefaultLimit(): int
        {
            return static::$globalDefaultLimit;
        }

        public static function make(int $total, ?int $currentPage = null, ?int $perPage = null): self
        {
            return new static($total, $currentPage, $perPage);
        }

        /**
         * Slices an array in memory and returns metadata + paginated items.
         * Symmetrical to CasaPagination.paginate() in JS.
         *
         * @param array $items
         * @param int|null $currentPage
         * @param int|null $perPage
         * @return array{pagination: self, items: array, total: int, page: int, perPage: int, totalPages: int, from: int, to: int}
         */
        public static function paginateArray(array $items, ?int $currentPage = null, ?int $perPage = null): array
        {
            $pagination = static::make(count($items), $currentPage, $perPage);
            $sliced = array_slice($items, $pagination->getOffset(), $pagination->getPerPage());
            return [
                'pagination' => $pagination,
                'items'      => $sliced,
                'total'      => $pagination->getTotal(),
                'page'       => $pagination->getCurrentPage(),
                'perPage'    => $pagination->getPerPage(),
                'totalPages' => $pagination->getTotalPages(),
                'from'       => $pagination->getFrom(),
                'to'         => $pagination->getTo(),
            ];
        }

        public function getTotal(): int
        {
            return $this->total;
        }

        public function getPerPage(): int
        {
            return $this->perPage;
        }

        public function getLimit(): int
        {
            return $this->perPage;
        }

        public function getCurrentPage(): int
        {
            return $this->currentPage;
        }

        public function getPage(): int
        {
            return $this->currentPage;
        }

        public function getTotalPages(): int
        {
            return $this->totalPages;
        }

        public function getLastPage(): int
        {
            return $this->totalPages;
        }

        public function getFirstPage(): int
        {
            return 1;
        }

        public function getOffset(): int
        {
            return $this->offset;
        }

        public function hasPrev(): bool
        {
            return $this->currentPage > 1;
        }

        public function hasNext(): bool
        {
            return $this->currentPage < $this->totalPages;
        }

        public function isFirst(): bool
        {
            return $this->currentPage <= 1;
        }

        public function isLast(): bool
        {
            return $this->currentPage >= $this->totalPages;
        }

        public function getPrevPage(): ?int
        {
            return $this->hasPrev() ? ($this->currentPage - 1) : null;
        }

        public function getNextPage(): ?int
        {
            return $this->hasNext() ? ($this->currentPage + 1) : null;
        }

        public function getFrom(): int
        {
            return $this->total === 0 ? 0 : $this->offset + 1;
        }

        public function getTo(): int
        {
            return $this->total === 0 ? 0 : min($this->total, $this->offset + $this->perPage);
        }

        public function getPageRange(int $onEachSide = 2): array
        {
            $onEachSide = max(1, $onEachSide);
            $windowSize = 1 + ($onEachSide * 2) + 2;

            if ($this->totalPages <= $windowSize) {
                return range(1, $this->totalPages);
            }

            $pages = [];
            $start = max(2, $this->currentPage - $onEachSide);
            $end = min($this->totalPages - 1, $this->currentPage + $onEachSide);

            $pages[] = 1;
            if ($start > 2) {
                $pages[] = '...';
            }
            for ($i = $start; $i <= $end; $i++) {
                $pages[] = $i;
            }
            if ($end < ($this->totalPages - 1)) {
                $pages[] = '...';
            }
            $pages[] = $this->totalPages;

            return $pages;
        }

        public function renderSummary(string $format = 'Showing %d to %d of %d entries'): string
        {
            return sprintf($format, $this->getFrom(), $this->getTo(), $this->total);
        }

        /**
         * Renders per-page select dropdown HTML matching CasaPagination in JS.
         */
        public function getPerPageSelectHtml(array $options = []): string
        {
            $selectId = $options['per_page_select_id'] ?? ($options['perPageSelectId'] ?? 'perPageSelect');
            $allowed = $options['per_page_options'] ?? ($options['perPageOptions'] ?? [10, 20, 50, 100]);
            $currentLimit = $this->perPage;

            $optsHtml = '';
            foreach ($allowed as $val) {
                $val = (int) $val;
                $label = ($val <= 0 || $val >= 9999) ? 'All' : (string) $val;
                $isSelected = ($val === $currentLimit || (($val <= 0 || $val >= 9999) && ($currentLimit <= 0 || $currentLimit >= 9999))) ? ' selected' : '';
                $optsHtml .= '<option value="' . $val . '"' . $isSelected . '>' . $label . '</option>';
            }

            $label = $options['per_page_label'] ?? 'Rows per page:';
            $selectClass = $options['per_page_select_class'] ?? 'form-select form-select-sm casa-per-page-select';
            $selectStyle = $options['per_page_select_style'] ?? 'width: auto; height: 31px;';

            return '<label class="mb-0 text-muted ms-2" style="font-size: 0.8rem; white-space: nowrap;" for="' . $this->escape($selectId) . '">' . $this->escape($label) . '</label>' .
                   '<select id="' . $this->escape($selectId) . '" class="' . $this->escape($selectClass) . '" style="' . $this->escape($selectStyle) . '">' .
                   $optsHtml .
                   '</select>';
        }

        /**
         * Renders unified pagination footer: count info on left, per-page dropdown + nav links on right.
         * Fully symmetrical to CasaPagination.getHtml() in JS.
         */
        public function renderFooter(array $options = []): string
        {
            $itemName = $options['item_name'] ?? ($options['itemName'] ?? 'entries');
            $showPerPage = $options['show_per_page'] ?? ($options['showPerPage'] ?? true);
            $wrapperClass = $options['wrapper_class'] ?? 'd-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 px-1';

            $summaryFormat = $options['summary_format'] ?? ('Showing %d–%d of %d ' . $itemName);
            $summaryText = $this->renderSummary($summaryFormat);

            $perPageHtml = $showPerPage ? $this->getPerPageSelectHtml($options) : '';
            $navHtml = $this->render($options);

            $html = '<div class="' . $this->escape($wrapperClass) . '">';
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<small class="text-muted me-1">' . $summaryText . '</small>';
            $html .= $perPageHtml;
            $html .= '</div>';
            $html .= $navHtml;
            $html .= '</div>';

            return $html;
        }

        public function toArray(): array
        {
            return [
                'total'        => $this->total,
                'per_page'     => $this->perPage,
                'current_page' => $this->currentPage,
                'total_pages'  => $this->totalPages,
                'first_page'   => 1,
                'last_page'    => $this->totalPages,
                'prev_page'    => $this->getPrevPage(),
                'next_page'    => $this->getNextPage(),
                'has_prev'     => $this->hasPrev(),
                'has_next'     => $this->hasNext(),
                'is_first'     => $this->isFirst(),
                'is_last'      => $this->isLast(),
                'offset'       => $this->offset,
                'from'         => $this->getFrom(),
                'to'           => $this->getTo(),
            ];
        }

        public function jsonSerialize(): array
        {
            return $this->toArray();
        }

        public function render(array $options = []): string
        {
            if ($this->totalPages <= 1 && empty($options['force_render'])) {
                return '';
            }

            $urlPattern    = $options['url_pattern'] ?? null;
            $pageParam     = $options['page_param'] ?? 'page';
            $showFirstLast = $options['show_first_last'] ?? true;
            $showPrevNext  = $options['show_prev_next'] ?? true;
            $firstText     = $options['first_text'] ?? '&laquo; First';
            $prevText      = $options['prev_text'] ?? '&lsaquo; Prev';
            $nextText      = $options['next_text'] ?? 'Next &rsaquo;';
            $lastText      = $options['last_text'] ?? 'Last &raquo;';
            $size          = !empty($options['size']) ? ' pagination-' . $this->escape($options['size']) : ' pagination-sm';
            $justify       = !empty($options['justify']) ? ' justify-content-' . $this->escape($options['justify']) : ' justify-content-center';
            $ulClass       = !empty($options['ul_class']) ? ' ' . $this->escape($options['ul_class']) : '';
            $linkClass     = !empty($options['link_class']) ? ' ' . $this->escape($options['link_class']) : '';
            $isAjax        = !empty($options['is_ajax']);
            $onEachSide    = isset($options['on_each_side']) ? (int) $options['on_each_side'] : 2;
            $baseUrl       = $options['base_url'] ?? null;
            $queryParams   = $options['query_params'] ?? null;
            $dataAttrs     = '';
            if (!empty($options['data_attributes']) && is_array($options['data_attributes'])) {
                foreach ($options['data_attributes'] as $attrKey => $attrVal) {
                    $dataAttrs .= ' data-' . $this->escape((string) $attrKey) . '="' . $this->escape((string) $attrVal) . '"';
                }
            }

            $buildUrl = function (int $page) use ($urlPattern, $pageParam, $isAjax, $baseUrl, $queryParams): string {
                if ($isAjax) {
                    return 'javascript:void(0);';
                }
                if (is_callable($urlPattern)) {
                    return (string) call_user_func($urlPattern, $page);
                }
                if (is_string($urlPattern) && str_contains($urlPattern, '%d')) {
                    return sprintf($urlPattern, $page);
                }

                $params = $queryParams ?? ($_GET ?? []);
                $params[$pageParam] = $page;
                $path = $baseUrl ?? (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
                return $this->escape($path . '?' . http_build_query($params));
            };

            $html = '<nav aria-label="Pagination Navigation">';
            $html .= '<ul class="pagination' . $size . $justify . $ulClass . ' mb-0">';

            if ($showFirstLast) {
                $html .= $this->renderItem(1, $firstText, $this->isFirst(), false, $this->isFirst() ? '#' : $buildUrl(1), $linkClass, $dataAttrs);
            }

            if ($showPrevNext) {
                $prevPage = $this->getPrevPage() ?? 1;
                $html .= $this->renderItem($prevPage, $prevText, !$this->hasPrev(), false, !$this->hasPrev() ? '#' : $buildUrl($prevPage), $linkClass, $dataAttrs);
            }

            foreach ($this->getPageRange($onEachSide) as $item) {
                if ($item === '...') {
                    $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                } else {
                    $page = (int) $item;
                    $html .= $this->renderItem($page, (string) $page, false, $page === $this->currentPage, $buildUrl($page), $linkClass, $dataAttrs);
                }
            }

            if ($showPrevNext) {
                $nextPage = $this->getNextPage() ?? $this->totalPages;
                $html .= $this->renderItem($nextPage, $nextText, !$this->hasNext(), false, !$this->hasNext() ? '#' : $buildUrl($nextPage), $linkClass, $dataAttrs);
            }

            if ($showFirstLast) {
                $lastPage = $this->totalPages;
                $html .= $this->renderItem($lastPage, $lastText, $this->isLast(), false, $this->isLast() ? '#' : $buildUrl($lastPage), $linkClass, $dataAttrs);
            }

            $html .= '</ul>';
            $html .= '</nav>';

            return $html;
        }

        protected function renderItem(int $page, string $text, bool $disabled, bool $active, string $href, string $linkClass, string $dataAttrs = ''): string
        {
            $class = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
            $tabindex = $disabled ? ' tabindex="-1" aria-disabled="true"' : '';
            $ariaCurrent = $active ? ' aria-current="page"' : '';
            return '<li class="' . $class . '"' . $ariaCurrent . '>' .
                   '<a class="page-link' . $linkClass . '" href="' . $href . '" data-page="' . $page . '"' . $dataAttrs . $tabindex . '>' . $text . '</a>' .
                   '</li>';
        }

        protected function escape(mixed $value): string
        {
            if (function_exists('e')) {
                return e($value);
            }
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace {
    if (!function_exists('paginate')) {
        function paginate(int $total, ?int $page = null, ?int $limit = null): \App\Helpers\Pagination
        {
            return \App\Helpers\Pagination::make($total, $page, $limit);
        }
    }
}

<?php

namespace App\Exports;

use App\Models\CentroSalud;
use App\Models\MetaIne;
use App\Models\Municipio;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Chart\Series;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Bar;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Table;
use PhpOffice\PhpPresentation\Slide\AbstractSlide;
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;

class PresentacionCAI
{
    private PhpPresentation $pptx;
    private int $municipioId;
    private string $periodo;
    private int $anio;
    private string $municipioNombre;
    private string $departamento;
    private string $redSalud;
    private $centros;
    private array $meses;
    private float $factorMeta;

    // ── Paleta institucional (colores vibrantes para pantalla grande) ──
    private const CYAN       = '00ACC1';
    private const CYAN_DARK  = '00838F';
    private const CYAN_BG    = 'E0F7FA';
    private const TEAL       = '00897B';
    private const TEAL_DARK  = '004D40';
    private const TEAL_LIGHT = 'B2DFDB';
    private const BLUE       = '1565C0';
    private const BLUE_LIGHT = 'E3F2FD';
    private const GREEN      = '2E7D32';
    private const ORANGE     = 'EF6C00';
    private const PINK       = 'AD1457';
    private const RED        = 'C62828';
    private const AMBER      = 'FF8F00';
    private const WHITE      = 'FFFFFF';
    private const BLACK      = '212121';
    private const GRAY_BG    = 'F5F5F5';
    private const GRAY_LIGHT = 'ECEFF1';
    private const GRAY_MID   = 'B0BEC5';
    private const GRAY_TEXT  = '546E7A';
    private const GRAY_DARK  = '37474F';

    // Colores para bandas de grupo etáreo
    private const BAND_MENOR1 = '00ACC1';
    private const BAND_1ANIO  = '0277BD';
    private const BAND_10ANIO = 'AD1457';

    // 1280×720 px (13.333″ × 7.5″)
    private const W = 1280;
    private const H = 720;
    private const M = 40;

    private const BAR_COLORS = ['00897B', '1565C0', 'EF6C00', 'C62828', '7B1FA2', 'AD1457', '283593'];

    public function __construct(int $municipioId, string $periodo, int $anio)
    {
        $this->municipioId = $municipioId;
        $this->periodo = $periodo;
        $this->anio = $anio;

        $mun = Municipio::findOrFail($municipioId);
        $this->municipioNombre = mb_strtoupper($mun->nombre);
        $this->departamento = $mun->departamento;
        $this->centros = CentroSalud::where('municipio_id', $municipioId)->orderByDesc('poblacion_ine')->get();
        // Obtener red de salud del primer centro del municipio (dinámico)
        $rawRed = $this->centros->first()?->red_salud ?? 'Red de Salud';
        $this->redSalud = str_starts_with($rawRed, 'Red') ? $rawRed : "Red de Salud {$rawRed}";

        $this->meses = match ($periodo) {
            'cai1', 'enero-abril' => [1, 2, 3, 4],
            'cai2'                => [1, 2, 3, 4, 5, 6, 7, 8],
            default               => range(1, 12),
        };
        // La Meta INE es siempre el dato anual completo, no se divide por el período.
        $this->factorMeta = 1.0;

        $this->pptx = new PhpPresentation();
        $this->pptx->getLayout()->setDocumentLayout(['cx' => 12192000, 'cy' => 6858000]);
    }

    public function generate(): string
    {
        $this->slidePortada();
        $this->slidePoblacion();
        $this->slidePiramide();
        $this->slideCoberturaVacunalMunicipio();
        foreach ($this->centros as $cs) {
            $this->slideCoberturaVacunalCentro($cs);
        }
        $this->slideDesercionMunicipal();
        $this->slideDesercionPorCentro();
        $this->slideSeparador('MICRONUTRIENTES', 'Suplementación con hierro y vitamina A');
        $this->slideVitaminaA();
        $this->slideHierroMenor1();
        $this->slideHierro2a5();
        $this->slideSeparador('MUJER EN EDAD FÉRTIL', 'Control prenatal, partos, anticoncepción');
        $this->slidePartoInstitucional();
        $this->slideMetodosModernos();
        $this->slideEmbarazoAdolescente();
        $this->slideSeparador('PROGRAMAS', 'Tuberculosis y sintomático respiratorio');
        $this->slideSintomaticoRespiratorio();
        $this->slideSeparador('INTEGRALIDAD', 'Análisis de variables cruzadas');
        $this->slideIntegralidad();
        $this->slideAtencionMujer();
        $this->slideCierre();

        $tmp = tempnam(sys_get_temp_dir(), 'cai_') . '.pptx';
        IOFactory::createWriter($this->pptx, 'PowerPoint2007')->save($tmp);
        return $tmp;
    }

    // ════════════════════════════════════════════════════
    //  LAYOUT HELPERS
    // ════════════════════════════════════════════════════

    private function newSlide(): AbstractSlide { return $this->pptx->createSlide(); }
    private function f(): string { return 'Calibri'; }
    private function periodoLabel(): string
    {
        return match ($this->periodo) {
            'cai1', 'enero-abril' => "ENERO – ABRIL {$this->anio}",
            'cai2'                => "ENERO – AGOSTO {$this->anio}",
            default               => "GESTIÓN {$this->anio}",
        };
    }
    private function shortName(string $n): string
    {
        return str_replace(['C.S.A. ', 'C.S. ', 'C.I.S. ', 'Hospital '], '', mb_strtoupper($n));
    }

    // ── Slide background with light gradient feel ──
    private function slideBg(AbstractSlide $slide): void
    {
        $bg = new BackgroundColor();
        $bg->setColor(new Color('FF' . self::GRAY_BG));
        $slide->setBackground($bg);
        // Left accent bar
        $this->rect($slide, 0, 0, 8, self::H, self::CYAN);
        // Bottom accent
        $this->rect($slide, 0, self::H - 4, self::W, 4, self::TEAL_DARK);
    }

    // ── Professional header ──
    private function banner(AbstractSlide $slide, string $title, string $sub = ''): void
    {
        $this->slideBg($slide);

        // Banner background con doble capa para efecto profundidad
        $bh = $sub ? 92 : 68;
        $this->rect($slide, 8, 0, self::W - 8, $bh, self::CYAN_DARK);
        $this->rect($slide, 8, 0, self::W - 8, $bh - 8, self::CYAN);

        // Acento inferior
        $this->rect($slide, 8, $bh - 4, self::W - 8, 4, self::TEAL_DARK);

        // Title
        $this->txt($slide, $title, 50, $sub ? 6 : 10, self::W - 100, 48, 26, self::WHITE, true, 'center');
        if ($sub) {
            $this->txt($slide, $sub, 50, 54, self::W - 100, 28, 13, self::CYAN_BG, false, 'center');
        }
    }

    // ── Period strip below banner ──
    private function periodStrip(AbstractSlide $slide, int $y): void
    {
        $this->rect($slide, 8, $y, self::W - 8, 32, self::WHITE);
        $slide->createLineShape(40, $y + 31, self::W - 40, $y + 31)
            ->getBorder()->setColor(new Color('FF' . self::GRAY_MID))->setLineWidth(1);
        $this->txt($slide, $this->periodoLabel(), 50, $y + 5, self::W - 100, 22, 13, self::GRAY_DARK, true, 'center');
    }

    // ── Footer ──
    private function footer(AbstractSlide $slide): void
    {
        // Barra inferior teal fina
        $this->rect($slide, 0, self::H - 30, self::W, 30, self::TEAL_DARK);
        $this->txt($slide, 'Fuente: SNIS - RNVe / INE', 12, self::H - 24, 320, 18, 8, self::GRAY_MID);
        $this->txt($slide, "SIMUES · Municipio {$this->municipioNombre} · {$this->periodoLabel()} · " . now()->format('d/m/Y'),
            440, self::H - 24, self::W - 460, 18, 8, self::TEAL_LIGHT, false, 'right');
    }

    // ── Text shorthand ──
    private function txt(AbstractSlide $slide, string $text, int $x, int $y, int $w, int $h,
        int $size = 14, string $color = self::BLACK, bool $bold = false, string $align = 'left'): RichText
    {
        $s = $slide->createRichTextShape()->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);
        $s->setAutoFit(RichText::AUTOFIT_NORMAL);
        $s->getActiveParagraph()->getAlignment()->setHorizontal(match ($align) {
            'center' => Alignment::HORIZONTAL_CENTER,
            'right'  => Alignment::HORIZONTAL_RIGHT,
            default  => Alignment::HORIZONTAL_LEFT,
        });
        $r = $s->createTextRun($text);
        $r->getFont()->setName($this->f())->setSize($size)->setBold($bold)->setColor(new Color('FF' . $color));
        return $s;
    }

    // ── Rectangle ──
    private function rect(AbstractSlide $slide, int $x, int $y, int $w, int $h, string $color): RichText
    {
        $s = $slide->createRichTextShape()->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $color);
        return $s;
    }

    // ── Bar chart (larger fonts for display) ──
    private function chart(AbstractSlide $slide, array $cats, array $seriesData,
        int $x, int $y, int $w, int $h): void
    {
        $bar = new Bar();
        $bar->setBarDirection(Bar::DIRECTION_VERTICAL);
        $bar->setBarGrouping(Bar::GROUPING_CLUSTERED);
        $bar->setGapWidthPercent(60);

        foreach ($seriesData as $i => [$label, $data, $color]) {
            $series = new Series($label, $data);
            $series->setShowValue(true);
            $series->setLabelPosition(Series::LABEL_OUTSIDEEND);
            $series->getFont()->setName($this->f())->setSize(9)->setBold(true)->setColor(new Color('FF333333'));
            $series->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $color);
            $bar->addSeries($series);
        }

        $chart = $slide->createChartShape();
        $chart->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);
        $chart->getPlotArea()->setType($bar);
        $chart->getPlotArea()->getAxisX()->setTitle('');
        $chart->getPlotArea()->getAxisX()->getFont()->setSize(9)->setName($this->f())->setBold(true);
        $chart->getPlotArea()->getAxisY()->setTitle('');
        $chart->getPlotArea()->getAxisY()->getFont()->setSize(9)->setName($this->f());
        $chart->getTitle()->setVisible(false);
        $chart->getLegend()->setVisible(count($seriesData) > 1);
        $chart->getLegend()->getFont()->setName($this->f())->setSize(10)->setBold(true);
    }

    // ── Professional table (large and readable) ──
    private function tbl(AbstractSlide $slide, array $headers, array $rows, int $x, int $y, int $w,
        int $rh = 28, string $hdrBg = self::TEAL): Table
    {
        $cols = count($headers);
        $table = $slide->createTableShape($cols);
        $table->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($rh * (count($rows) + 1));

        // Header row
        $hr = $table->createRow();
        $hr->setHeight($rh + 4);
        foreach ($headers as $i => $h) {
            $cell = $hr->getCell($i);
            $cell->setWidth(intdiv($w, $cols));
            $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $hdrBg);
            $cell->getBorders()->getTop()->setLineStyle(Border::LINE_NONE);
            $cell->getBorders()->getBottom()->setColor(new Color('FF' . self::WHITE))->setLineWidth(2);
            $cell->getBorders()->getLeft()->setColor(new Color('FF' . self::WHITE))->setLineWidth(1);
            $cell->getBorders()->getRight()->setColor(new Color('FF' . self::WHITE))->setLineWidth(1);
            $r = $cell->createTextRun($h);
            $r->getFont()->setName($this->f())->setSize(11)->setBold(true)->setColor(new Color('FF' . self::WHITE));
            $cell->getActiveParagraph()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Data rows
        foreach ($rows as $ri => $row) {
            $dr = $table->createRow();
            $dr->setHeight($rh);
            foreach ($row as $ci => $val) {
                $cell = $dr->getCell($ci);
                $bgc = $ri % 2 === 0 ? self::WHITE : self::GRAY_LIGHT;
                $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $bgc);
                $cell->getBorders()->getTop()->setLineStyle(Border::LINE_NONE);
                $cell->getBorders()->getBottom()->setColor(new Color('FFE0E0E0'))->setLineWidth(1);
                $cell->getBorders()->getLeft()->setLineStyle(Border::LINE_NONE);
                $cell->getBorders()->getRight()->setLineStyle(Border::LINE_NONE);
                $text = is_array($val) ? $val[0] : (string) $val;
                $r = $cell->createTextRun($text);
                $r->getFont()->setName($this->f())->setSize(11)->setColor(new Color('FF' . self::BLACK));
                if ($ci === 0) {
                    $r->getFont()->setBold(true);
                }
                $cell->getActiveParagraph()->getAlignment()
                    ->setHorizontal($ci === 0 ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }
        }
        return $table;
    }

    // ── Semáforo color grid ──
    // $hdrColors: array of hex colors per column (index => color); defaults to BLUE
    // $firstColW: width of first column in px; 0 = equal widths for all columns
    private function colorGrid(AbstractSlide $slide, array $headers, array $rows,
        int $x, int $y, int $w, int $rh = 30, array $hdrColors = [], int $firstColW = 180): Table
    {
        $cols  = count($headers);
        $colW0 = $firstColW > 0 ? $firstColW : intdiv($w, $cols);
        $colWN = ($cols > 1 && $firstColW > 0) ? intdiv($w - $firstColW, $cols - 1) : $colW0;

        $table = $slide->createTableShape($cols);
        $table->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($rh * (count($rows) + 1));

        $hr = $table->createRow();
        $hr->setHeight($rh + 2);
        foreach ($headers as $i => $h) {
            $cell = $hr->getCell($i);
            $cell->setWidth($i === 0 ? $colW0 : $colWN);
            $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                ->setARGB('FF' . ($hdrColors[$i] ?? self::BLUE));
            $cell->getBorders()->getTop()->setLineStyle(Border::LINE_NONE);
            $cell->getBorders()->getBottom()->setLineStyle(Border::LINE_NONE);
            $cell->getBorders()->getLeft()->setColor(new Color('FF' . self::WHITE))->setLineWidth(1);
            $cell->getBorders()->getRight()->setColor(new Color('FF' . self::WHITE))->setLineWidth(1);
            $r = $cell->createTextRun($h);
            $r->getFont()->setName($this->f())->setSize(8)->setBold(true)->setColor(new Color('FF' . self::WHITE));
            $cell->getActiveParagraph()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach ($rows as $row) {
            $dr = $table->createRow();
            $dr->setHeight($rh);
            foreach ($row as $ci => $val) {
                $cell = $dr->getCell($ci);
                $cell->getBorders()->getTop()->setColor(new Color('FF' . self::WHITE))->setLineWidth(2);
                $cell->getBorders()->getBottom()->setColor(new Color('FF' . self::WHITE))->setLineWidth(2);
                $cell->getBorders()->getLeft()->setColor(new Color('FF' . self::WHITE))->setLineWidth(2);
                $cell->getBorders()->getRight()->setColor(new Color('FF' . self::WHITE))->setLineWidth(2);
                if (is_array($val)) {
                    [$text, $bg] = $val;
                    $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $bg);
                    $r = $cell->createTextRun((string) $text);
                    $r->getFont()->setName($this->f())->setSize(9)->setBold(true)->setColor(new Color('FF' . self::WHITE));
                } else {
                    $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . self::GRAY_LIGHT);
                    $r = $cell->createTextRun((string) $val);
                    $r->getFont()->setName($this->f())->setSize(9)->setBold(true)->setColor(new Color('FF' . self::BLACK));
                }
                $cell->getActiveParagraph()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
        }
        return $table;
    }

    // ════════════════════════════════════════════════════
    //  DATA
    // ════════════════════════════════════════════════════

    private function metaIne(int $csId, string $grupo): int
    {
        return (int) MetaIne::where('centro_salud_id', $csId)
            ->where('anio', $this->anio)->where('grupo_etareo', $grupo)->value('cantidad');
    }
    private function sumVacuna(int $csId, string $tipo): int
    {
        return (int) DB::table('prest_vacunas')
            ->where('centro_salud_id', $csId)->where('anio', $this->anio)
            ->whereIn('mes', $this->meses)->where('tipo_vacuna', $tipo)
            ->selectRaw('SUM(dentro_m+dentro_f+fuera_m+fuera_f) as t')->value('t');
    }
    private function cobPct(int $applied, int $meta): float
    {
        $adj = max(1, round($meta * $this->factorMeta));
        return $adj > 0 ? round($applied / $adj * 100, 1) : 0;
    }
    private function semaforoColor(float $pct): string
    {
        if ($pct >= 95) return '4CAF50';
        if ($pct >= 80) return 'FFC107';
        if ($pct >= 50) return 'FF9800';
        return 'F44336';
    }

    // ════════════════════════════════════════════════════
    //  SLIDES
    // ════════════════════════════════════════════════════

    private function slidePortada(): void
    {
        $this->pptx->removeSlideByIndex(0);
        $slide = $this->pptx->createSlide();

        // Full background
        $bg = new BackgroundColor();
        $bg->setColor(new Color('FF' . self::WHITE));
        $slide->setBackground($bg);

        // Top decorative band (thick)
        $this->rect($slide, 0, 0, self::W, 12, self::CYAN);

        // "Vacunación gestión YYYY"
        $this->txt($slide, "Vacunación gestión {$this->anio}", 0, 40, self::W, 40, 22, self::GRAY_TEXT, false, 'center');

        // Main title block with background
        $this->rect($slide, 60, 100, self::W - 120, 150, self::CYAN);
        // Inner shadow effect
        $this->rect($slide, 60, 100, self::W - 120, 6, self::CYAN_DARK);
        $this->txt($slide, 'COMITÉ DE ANÁLISIS DE INFORMACIÓN', 80, 112, self::W - 160, 50, 30, self::WHITE, true, 'center');
        $this->txt($slide, "MUNICIPIO DE {$this->municipioNombre}", 80, 168, self::W - 160, 55, 38, self::WHITE, true, 'center');

        // Period block
        $this->rect($slide, 250, 280, self::W - 500, 50, self::TEAL_DARK);
        $this->txt($slide, $this->periodoLabel(), 260, 290, self::W - 520, 30, 22, self::WHITE, true, 'center');

        // Department
        $this->txt($slide, "Departamento de {$this->departamento}", 0, 360, self::W, 35, 18, self::GRAY_DARK, false, 'center');

        // Establishments
        $this->rect($slide, 400, 420, self::W - 800, 40, self::GRAY_LIGHT);
        $this->txt($slide, $this->centros->count() . ' Establecimientos de Salud', 400, 426, self::W - 800, 28, 16, self::TEAL, true, 'center');

        // Decorative bottom
        $this->rect($slide, 0, self::H - 70, self::W, 70, self::TEAL_DARK);
        $this->rect($slide, 0, self::H - 70, self::W, 5, self::CYAN);
        $this->txt($slide, "{$this->redSalud} · Ministerio de Salud y Deportes · Bolivia",
            40, self::H - 55, self::W - 80, 25, 14, self::WHITE, true, 'center');
        $this->txt($slide, 'SIMUES — Sistema de Información Municipal de Establecimientos de Salud',
            40, self::H - 32, self::W - 80, 20, 11, self::TEAL_LIGHT, false, 'center');
    }

    private function slidePoblacion(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, "POBLACIÓN MUNICIPIO {$this->municipioNombre}", "Proyección INE — Gestión {$this->anio}");
        $this->periodStrip($slide, 90);

        $cats = [];
        $pobs = [];
        $rows = [];
        $total = 0;
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $cats[] = $n;
            $pobs[$n] = $cs->poblacion_ine;
            $rows[] = [$cs->nombre, number_format($cs->poblacion_ine)];
            $total += $cs->poblacion_ine;
        }
        $rows[] = ['TOTAL MUNICIPIO', number_format($total)];

        $this->chart($slide, $cats, [['Población', $pobs, self::TEAL]],
            self::M, 126, self::W - self::M * 2, 320);

        $this->tbl($slide, ['Establecimiento', 'Población INE'], $rows,
            self::M + 80, 460, 600, 22);
        $this->footer($slide);
    }

    private function slidePiramide(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'PIRÁMIDE POBLACIONAL', "Distribución por grupo etáreo — INE {$this->anio}");
        $this->periodStrip($slide, 90);

        $grupos = ['menor_1','1_anio','2_anios','3_anios','4_anios','5_9','10_14','15_19','20_39','40_49','50_59','mayor_60'];
        $labels = ['< 1 año','1 año','2 años','3 años','4 años','5-9','10-14','15-19','20-39','40-49','50-59','60+'];

        $cats = [];
        $vals = [];
        $totalPop = 0;
        foreach ($grupos as $i => $g) {
            $s = 0;
            foreach ($this->centros as $cs) $s += $this->metaIne($cs->id, $g);
            $cats[] = $labels[$i];
            $vals[$labels[$i]] = $s;
            $totalPop += $s;
        }

        $this->chart($slide, $cats, [['Población', $vals, self::TEAL]],
            self::M, 126, self::W - self::M * 2, 380);

        // Total population badge
        $this->rect($slide, self::M, 520, 380, 36, self::TEAL);
        $this->txt($slide, "  Población total INE: " . number_format($totalPop), self::M + 10, 526, 360, 24, 14, self::WHITE, true);
        $this->footer($slide);
    }

    // ── Vacunación municipal ──

    private function slideCoberturaVacunalMunicipio(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, "MUNICIPIO {$this->municipioNombre}",
            'Indicadores de Acceso Esquema Regular de Vacunaciones');
        $this->periodStrip($slide, 90);

        // Age group color bands
        $bandY = 125;
        $totalVac = 19;
        $cW = (self::W - 100) / $totalVac;

        $this->rect($slide, 60, $bandY, (int)($cW * 14), 26, self::BAND_MENOR1);
        $this->txt($slide, 'Menor a 1 año', 60, $bandY + 3, (int)($cW * 14), 20, 10, self::WHITE, true, 'center');
        $x2 = 60 + (int)($cW * 14);
        $this->rect($slide, $x2, $bandY, (int)($cW * 3), 26, self::BAND_1ANIO);
        $this->txt($slide, 'De 1 año', $x2, $bandY + 3, (int)($cW * 3), 20, 9, self::WHITE, true, 'center');
        $x3 = $x2 + (int)($cW * 3);
        $this->rect($slide, $x3, $bandY, (int)($cW * 2), 26, self::BAND_10ANIO);
        $this->txt($slide, 'Niñas 10 años', $x3, $bandY + 3, (int)($cW * 2), 20, 8, self::WHITE, true, 'center');

        $vacData = $this->getVacunasMunicipio();

        $this->chart($slide, $vacData['labels'], [['% Cobertura', $vacData['vals'], self::TEAL]],
            20, 152, self::W - 40, 380);

        // Meta indicator
        $metaPct = round($this->factorMeta * 100);
        $this->rect($slide, self::W - 260, 545, 230, 28, self::RED);
        $this->txt($slide, "◄  Meta {$metaPct}% acum.  ►", self::W - 256, 549, 222, 20, 11, self::WHITE, true, 'center');
        $this->footer($slide);
    }

    private function slideCoberturaVacunalCentro(CentroSalud $cs): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, mb_strtoupper($cs->nombre), 'Indicadores de Acceso Esquema Regular de Vacunaciones');
        $this->periodStrip($slide, 90);

        // Age group bands
        $bandY = 125;
        $totalVac = 19;
        $cW = (self::W - 100) / $totalVac;
        $this->rect($slide, 60, $bandY, (int)($cW * 14), 26, self::BAND_MENOR1);
        $this->txt($slide, 'Menor a 1 año', 60, $bandY + 3, (int)($cW * 14), 20, 10, self::WHITE, true, 'center');
        $x2 = 60 + (int)($cW * 14);
        $this->rect($slide, $x2, $bandY, (int)($cW * 3), 26, self::BAND_1ANIO);
        $this->txt($slide, '1 año', $x2, $bandY + 3, (int)($cW * 3), 20, 9, self::WHITE, true, 'center');
        $x3 = $x2 + (int)($cW * 3);
        $this->rect($slide, $x3, $bandY, (int)($cW * 2), 26, self::BAND_10ANIO);
        $this->txt($slide, '10 años', $x3, $bandY + 3, (int)($cW * 2), 20, 8, self::WHITE, true, 'center');

        $vacData = $this->getVacunasCentro($cs);
        $this->chart($slide, $vacData['labels'], [['% Cobertura', $vacData['vals'], self::TEAL]],
            20, 152, self::W - 40, 380);

        $metaPct = round($this->factorMeta * 100);
        $this->rect($slide, self::W - 260, 545, 230, 28, self::RED);
        $this->txt($slide, "◄  Meta {$metaPct}% acum.  ►", self::W - 256, 549, 222, 20, 11, self::WHITE, true, 'center');
        $this->footer($slide);
    }

    private function getVacunasMunicipio(): array
    {
        $vacunas = $this->vacunasMap();
        $labels = $this->vacunasLabels();
        $vals = [];
        $i = 0;
        foreach ($vacunas as $vac => $grupo) {
            $app = 0; $meta = 0;
            foreach ($this->centros as $cs) {
                $app += $this->sumVacuna($cs->id, $vac);
                $meta += $this->metaIne($cs->id, $grupo);
            }
            $vals[$labels[$i]] = $this->cobPct($app, $meta);
            $i++;
        }
        return ['labels' => $labels, 'vals' => $vals];
    }

    private function getVacunasCentro(CentroSalud $cs): array
    {
        $vacunas = $this->vacunasMap();
        $labels = $this->vacunasLabels();
        $vals = [];
        $i = 0;
        foreach ($vacunas as $vac => $grupo) {
            $app = $this->sumVacuna($cs->id, $vac);
            $meta = $this->metaIne($cs->id, $grupo);
            $vals[$labels[$i]] = $this->cobPct($app, $meta);
            $i++;
        }
        return ['labels' => $labels, 'vals' => $vals];
    }

    private function vacunasMap(): array
    {
        return [
            'BCG'=>'menor_1','Pentavalente_1'=>'menor_1','Pentavalente_2'=>'menor_1','Pentavalente_3'=>'menor_1',
            'IPV_1'=>'menor_1','bOPV_2'=>'menor_1','IPV_3'=>'menor_1',
            'Antirotavirica_1'=>'menor_1','Antirotavirica_2'=>'menor_1',
            'Antineumococica_1'=>'menor_1','Antineumococica_2'=>'menor_1','Antineumococica_3'=>'menor_1',
            'Influenza_6_11m_1'=>'menor_1','Influenza_7_11m_2'=>'menor_1',
            'SRP_1'=>'1_anio','SRP_2'=>'1_anio','Antiamarilica'=>'1_anio',
            'VPH_1'=>'10_14','VPH_2'=>'10_14',
        ];
    }

    private function vacunasLabels(): array
    {
        return [
            '% BCG','% 1RA PENTA','% 2DA PENTA','% 3RA PENTA',
            '% 1RA POLIO','% 2DA POLIO','% 3RA POLIO','% 1RA ROTA','% 2DA ROTA',
            '% 1RA NEUMO','% 2DA NEUMO','% 3RA NEUMO','% 1RA INFLUENZA','% 2DA INFLUENZA',
            '% 1RA SRP','% 2DA SRP','% FA','% VPH NIÑAS','% VPH NIÑOS',
        ];
    }

    // ── Deserción ──

    private function slideDesercionMunicipal(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'TASA DE DESERCIÓN', 'Indicadores de Deserción — Esquema Regular de Vacunaciones');
        $this->periodStrip($slide, 90);

        $tP1 = $tP3 = $tS1 = $tS2 = 0;
        foreach ($this->centros as $cs) {
            $tP1 += $this->sumVacuna($cs->id, 'Pentavalente_1');
            $tP3 += $this->sumVacuna($cs->id, 'Pentavalente_3');
            $tS1 += $this->sumVacuna($cs->id, 'SRP_1');
            $tS2 += $this->sumVacuna($cs->id, 'SRP_2');
        }
        $desP = $tP1 - $tP3;
        $dP   = $tP1 > 0 ? round($desP / $tP1 * 100, 1) : 0.0;
        $desS = $tS1 - $tS2;
        $dS   = $tS1 > 0 ? round($desS / $tS1 * 100, 1) : 0.0;

        // 8 barras: pob. vacunada + deserción + % tasa para Pentavalente y SRP
        $cats = [
            'Pob.Vac.\nPenta 1ra', 'Pob.Vac.\nPenta 3ra', 'Deserción\nPenta1-3', '%Tasa\nPenta',
            'Pob.Vac.\nSRP 1ra',   'Pob.Vac.\nSRP 2da',   'Deserción\nSRP1-2',   '%Tasa\nSRP',
        ];
        $vals = array_combine($cats, [
            (float) $tP1, (float) $tP3, (float) $desP, $dP,
            (float) $tS1, (float) $tS2, (float) $desS, $dS,
        ]);
        $this->chart($slide, $cats, [['Indicadores', $vals, self::TEAL]], 20, 126, self::W - 40, 305);

        // Tabla de 8 columnas con los mismos indicadores — sin primera columna especial
        $hC = [
            self::CYAN, self::CYAN, self::TEAL, self::TEAL_DARK,
            self::BLUE, self::BLUE, self::BLUE, self::TEAL_DARK,
        ];
        $this->colorGrid($slide,
            ['Pob. Vac.\nPenta 1ra', 'Pob. Vac.\nPenta 3ra', 'Deserción\nPenta', '%Tasa\nPenta',
             'Pob. Vac.\nSRP 1ra',   'Pob. Vac.\nSRP 2da',   'Deserción\nSRP',   '%Tasa\nSRP'],
            [[
                $tP1, $tP3, $desP, ["{$dP}%", self::TEAL_DARK],
                $tS1, $tS2, $desS, ["{$dS}%", self::TEAL_DARK],
            ]],
            20, 445, self::W - 40, 42, $hC, 0);
        $this->footer($slide);
    }

    private function slideDesercionPorCentro(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'TASA DE DESERCIÓN CON VACUNA PENTAVALENTE', 'Por establecimiento de salud');
        $this->periodStrip($slide, 90);

        $cats = [];
        $tasas = [];
        $rows = [];
        foreach ($this->centros as $cs) {
            $p1 = $this->sumVacuna($cs->id, 'Pentavalente_1');
            $p3 = $this->sumVacuna($cs->id, 'Pentavalente_3');
            $t = $p1 > 0 ? round(($p1 - $p3) / $p1 * 100, 1) : 0;
            $n = $this->shortName($cs->nombre);
            $cats[] = $n;
            $tasas[$n] = $t;
            $rows[] = [$cs->nombre, $p1, $p3, "{$t}%"];
        }

        $this->chart($slide, $cats, [['Tasa %', $tasas, self::ORANGE]],
            self::M, 126, self::W - self::M * 2, 260);
        $this->tbl($slide, ['Establecimiento','1ra Penta','3ra Penta','Tasa'],
            $rows, self::M + 60, 400, self::W - 160, 24);
        $this->footer($slide);
    }

    // ── Separador ──

    private function slideSeparador(string $titulo, string $sub = ''): void
    {
        $slide = $this->newSlide();
        $bg = new BackgroundColor();
        $bg->setColor(new Color('FF' . self::WHITE));
        $slide->setBackground($bg);

        // Top + bottom accents
        $this->rect($slide, 0, 0, self::W, 12, self::CYAN);
        $this->rect($slide, 0, self::H - 12, self::W, 12, self::TEAL_DARK);

        // Side decorations
        $this->rect($slide, 0, 200, 8, 320, self::CYAN);
        $this->rect($slide, self::W - 8, 200, 8, 320, self::CYAN);

        // Center block
        $this->rect($slide, 160, 240, self::W - 320, 130, self::CYAN);
        $this->rect($slide, 160, 240, self::W - 320, 6, self::TEAL_DARK);
        $this->txt($slide, $titulo, 180, 260, self::W - 360, 50, 34, self::WHITE, true, 'center');
        if ($sub) {
            $this->txt($slide, $sub, 180, 315, self::W - 360, 35, 16, self::WHITE, false, 'center');
        }

        // Decorative lines
        $slide->createLineShape(60, 305, 150, 305)
            ->getBorder()->setColor(new Color('FF' . self::CYAN))->setLineWidth(3);
        $slide->createLineShape(self::W - 150, 305, self::W - 60, 305)
            ->getBorder()->setColor(new Color('FF' . self::CYAN))->setLineWidth(3);
    }

    // ── Micronutrientes ──

    private function slideVitaminaA(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'COBERTURA VITAMINA A — NIÑOS 2 A 5 AÑOS');
        $this->periodStrip($slide, 65);

        $cats = []; $d1 = []; $d2 = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $v1 = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'vitA_2_5_1ra')->sum('cantidad');
            $v2 = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'vitA_2_5_2da')->sum('cantidad');
            $cats[] = $n; $d1[$n] = $v1; $d2[$n] = $v2;
            $rows[] = [$cs->nombre, $v1, $v2];
        }

        $this->chart($slide, $cats, [['1ra Dosis', $d1, self::TEAL], ['2da Dosis', $d2, self::BLUE]],
            self::M, 100, self::W - self::M * 2, 300);
        $this->tbl($slide, ['Establecimiento','1ra Dosis Vit A','2da Dosis Vit A'],
            $rows, self::M + 60, 415, self::W - 160, 24);
        $this->footer($slide);
    }

    private function slideHierroMenor1(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'HIERRO EN MENORES DE 1 AÑO');
        $this->periodStrip($slide, 65);

        $cats = []; $mV = []; $hV = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $meta = $this->metaIne($cs->id, 'menor_1');
            $h = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'hierro_menor_1')->sum('cantidad');
            $pct = $this->cobPct($h, $meta);
            $cats[] = $n;
            $mV[$n] = round($meta * $this->factorMeta);
            $hV[$n] = $h;
            $rows[] = [$cs->nombre, round($meta * $this->factorMeta), $h, "{$pct}%"];
        }

        $this->chart($slide, $cats, [['Meta ajustada', $mV, self::GRAY_MID], ['Hierro entregado', $hV, self::TEAL]],
            self::M, 100, self::W - self::M * 2, 280);
        $this->tbl($slide, ['Establecimiento','Meta < 1 año','Hierro','%'],
            $rows, self::M + 60, 395, self::W - 160, 24);
        $this->footer($slide);
    }

    private function slideHierro2a5(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'HIERRO EN NIÑOS DE 2 A 5 AÑOS');
        $this->periodStrip($slide, 65);

        $cats = []; $hV = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $h = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'hierro_2_5')->sum('cantidad');
            $m25 = $this->metaIne($cs->id,'2_anios') + $this->metaIne($cs->id,'3_anios') + $this->metaIne($cs->id,'4_anios');
            $pct = $this->cobPct($h, $m25);
            $cats[] = $n; $hV[$n] = $h;
            $rows[] = [$cs->nombre, round($m25 * $this->factorMeta), $h, "{$pct}%"];
        }

        $this->chart($slide, $cats, [['Hierro entregado', $hV, self::TEAL]],
            self::M, 100, self::W - self::M * 2, 280);
        $this->tbl($slide, ['Establecimiento','Meta 2-4 años','Hierro','%'],
            $rows, self::M + 60, 395, self::W - 160, 24);
        $this->footer($slide);
    }

    // ── MEF ──

    private function slidePartoInstitucional(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'COBERTURA PARTO INSTITUCIONAL');
        $this->periodStrip($slide, 65);

        $cats = []; $esp = []; $inst = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $mP = round($this->metaIne($cs->id, 'partos_esperados') * $this->factorMeta);
            $pi = (int) DB::table('prest_partos')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('lugar', 'servicio')->sum('cantidad');
            $pct = $mP > 0 ? round($pi / $mP * 100, 1) : 0;
            $cats[] = $n; $esp[$n] = $mP; $inst[$n] = $pi;
            $rows[] = [$cs->nombre, $mP, $pi, "{$pct}%"];
        }

        $this->chart($slide, $cats,
            [['Partos esperados', $esp, self::GRAY_MID], ['Partos institucionales', $inst, self::TEAL]],
            self::M, 100, self::W - self::M * 2, 280);
        $this->tbl($slide, ['Establecimiento','Esperados','Institucionales','%'],
            $rows, self::M + 60, 395, self::W - 160, 24);
        $this->footer($slide);
    }

    private function slideMetodosModernos(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'MÉTODOS MODERNOS — USUARIAS NUEVAS');
        $this->periodStrip($slide, 65);

        $cats = []; $mefV = []; $metV = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $mef = round($this->metaIne($cs->id, 'mef_15_40') * $this->factorMeta);
            $met = (int) DB::table('prest_anticoncepcion')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo_usuaria', 'nueva')->sum('cantidad');
            $pct = $mef > 0 ? round($met / $mef * 100, 1) : 0;
            $cats[] = $n; $mefV[$n] = $mef; $metV[$n] = $met;
            $rows[] = [$cs->nombre, $mef, $met, "{$pct}%"];
        }

        $this->chart($slide, $cats,
            [['MEF', $mefV, self::GRAY_MID], ['Métodos modernos', $metV, self::PINK]],
            self::M, 100, self::W - self::M * 2, 280);
        $this->tbl($slide, ['Establecimiento','MEF','Métodos nuevas','%'],
            $rows, self::M + 60, 395, self::W - 160, 24);
        $this->footer($slide);
    }

    private function slideEmbarazoAdolescente(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'EMBARAZO EN ADOLESCENTES');
        $this->periodStrip($slide, 65);

        $cats = []; $cpnV = []; $adoV = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $cpn = (int) DB::table('prest_prenatales')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo_control', 'nueva_1er_trim')->sum('dentro');
            $ado = (int) DB::table('prest_prenatales')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo_control', 'nueva_1er_trim')->whereIn('grupo_etareo', ['10_14','15_19'])
                ->sum('dentro');
            $pct = $cpn > 0 ? round($ado / $cpn * 100, 1) : 0;
            $cats[] = $n; $cpnV[$n] = $cpn; $adoV[$n] = $ado;
            $rows[] = [$cs->nombre, $cpn, $ado, "{$pct}%"];
        }

        $this->chart($slide, $cats,
            [['CPN nuevas', $cpnV, self::TEAL], ['Emb. adolescente', $adoV, self::PINK]],
            self::M, 100, self::W - self::M * 2, 280);
        $this->tbl($slide, ['Establecimiento','CPN Nuevas','Emb. Adolescentes','%'],
            $rows, self::M + 60, 395, self::W - 160, 24);
        $this->footer($slide);
    }

    // ── Programas ──

    private function slideSintomaticoRespiratorio(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'SINTOMÁTICO RESPIRATORIO');
        $this->periodStrip($slide, 65);

        $cats = []; $srV = []; $rows = [];
        foreach ($this->centros as $cs) {
            $n = $this->shortName($cs->nombre);
            $ct = (int) DB::table('prest_consulta_externa')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->selectRaw('SUM(primera_m+primera_f) as t')->value('t');
            $cats[] = $n; $srV[$n] = $ct;
            $rows[] = [$cs->nombre, number_format($ct)];
        }

        $this->chart($slide, $cats, [['Consultantes', $srV, self::TEAL]],
            self::M, 100, self::W - self::M * 2, 300);
        $this->tbl($slide, ['Establecimiento','Total Consultas'],
            $rows, self::M + 150, 415, self::W - 340, 26);
        $this->footer($slide);
    }

    // ── Integralidad ──

    private function slideIntegralidad(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'INTEGRALIDAD DE VACUNAS — NIÑOS < 5 AÑOS', 'Semáforo de cobertura por establecimiento');

        // BCG se muestra separado (columna especial); resto de vacunas en semáforo
        $vacTypes  = ['Pentavalente_1','Pentavalente_2','Pentavalente_3',
                      'IPV_1','bOPV_2','IPV_3',
                      'Antirotavirica_1','Antirotavirica_2',
                      'Antineumococica_1','Antineumococica_2','Antineumococica_3',
                      'SRP_1','Antiamarilica'];
        $vacLabels = ['1P','2P','3P','1IPV','2OPV','3OPV','1Rot','2Rot','1Neu','2Neu','3Neu','SRP','FA'];

        // Columnas: Establecimiento | Partos | BCG | Dif. | [13 vacunas]
        $headers = array_merge(['Establecimiento', 'Partos', 'BCG', 'Dif.'], $vacLabels);

        // Colores de encabezado por grupo de columnas
        $hC = array_merge(
            [self::TEAL_DARK, self::CYAN, self::TEAL, self::ORANGE],
            array_fill(0, 3, self::BLUE),     // Penta 1-3
            array_fill(0, 3, '0277BD'),       // IPV / OPV
            array_fill(0, 2, '2E7D32'),       // Rota
            array_fill(0, 3, 'AD1457'),       // Neumo
            ['6A1B9A', '4527A0']              // SRP, FA
        );

        $rows   = [];
        $totals = ['partos' => 0, 'bcg' => 0, 'vac' => []];

        foreach ($this->centros as $cs) {
            $partos = (int) DB::table('prest_partos')
                ->where('centro_salud_id', $cs->id)->where('anio', $this->anio)
                ->whereIn('mes', $this->meses)->sum('cantidad');
            $bcg    = $this->sumVacuna($cs->id, 'BCG');
            $dif    = $bcg - $partos;
            $metaM1 = $this->metaIne($cs->id, 'menor_1');

            $row = [
                $this->shortName($cs->nombre),
                $partos,
                $bcg,
                $dif === 0 ? '0' : ($dif > 0 ? "+{$dif}" : (string) $dif),
            ];

            $totals['partos'] += $partos;
            $totals['bcg']    += $bcg;

            foreach ($vacTypes as $vi => $vt) {
                $app = $this->sumVacuna($cs->id, $vt);
                $pct = $this->cobPct($app, $metaM1);
                $row[] = [$app > 0 ? (string) $app : '0', $this->semaforoColor($pct)];
                $totals['vac'][$vi] = ($totals['vac'][$vi] ?? 0) + $app;
            }
            $rows[] = $row;
        }

        // Fila total RED (municipio)
        $tP  = $totals['partos'];
        $tB  = $totals['bcg'];
        $tD  = $tB - $tP;
        $tMeta = 0;
        foreach ($this->centros as $cs) $tMeta += $this->metaIne($cs->id, 'menor_1');
        $totalRow = [
            ['RED', self::TEAL_DARK],
            [$tP, self::TEAL_DARK],
            [$tB, self::TEAL_DARK],
            [($tD >= 0 ? "+{$tD}" : (string) $tD), self::TEAL_DARK],
        ];
        foreach ($vacTypes as $vi => $vt) {
            $app = $totals['vac'][$vi] ?? 0;
            $pct = $this->cobPct($app, $tMeta);
            $totalRow[] = [$app > 0 ? (string) $app : '0', $this->semaforoColor($pct)];
        }
        $rows[] = $totalRow;

        $this->colorGrid($slide, $headers, $rows, 10, 95, self::W - 20, 26, $hC, 130);

        // Leyenda semáforo
        $ly = self::H - 55;
        $this->rect($slide, 60, $ly, 16, 13, '4CAF50');
        $this->txt($slide, '≥ 95%', 80, $ly, 55, 14, 9, self::BLACK, true);
        $this->rect($slide, 145, $ly, 16, 13, 'FFC107');
        $this->txt($slide, '80-94%', 165, $ly, 60, 14, 9, self::BLACK, true);
        $this->rect($slide, 238, $ly, 16, 13, 'FF9800');
        $this->txt($slide, '50-79%', 258, $ly, 60, 14, 9, self::BLACK, true);
        $this->rect($slide, 330, $ly, 16, 13, 'F44336');
        $this->txt($slide, '< 50%', 350, $ly, 55, 14, 9, self::BLACK, true);
        $this->footer($slide);
    }

    private function slideAtencionMujer(): void
    {
        $slide = $this->newSlide();
        $this->banner($slide, 'ATENCIÓN INTEGRAL A LA MUJER', 'Indicadores de salud materna por establecimiento');

        $headers = [
            'Establecimiento', 'CPN 1er\nTrim.', '4to CPN',
            'Partos\nServicio', 'Partos\nDomicilio', 'Partos\nPartera', 'Total\nPartos',
            'Vit A\nPuérp.', 'Hierro\nPuérp.', 'Diferencia',
        ];
        $hC = [
            self::TEAL_DARK,
            'F9A825', '2E7D32',                        // CPN (ámbar, verde)
            '1565C0', '1565C0', '1565C0', '4527A0',   // Partos (azul×3, púrpura)
            'EF6C00', 'EF6C00',                        // Micronutrientes (naranja×2)
            '546E7A',                                  // Diferencia (gris)
        ];

        $rows = [];
        $tots = array_fill(0, 8, 0); // índices 0-7: c1,c4,pI,pD,pP,tP,vA,hP

        foreach ($this->centros as $cs) {
            $c1 = (int) DB::table('prest_prenatales')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo_control', 'nueva_1er_trim')->sum('dentro');
            $c4 = (int) DB::table('prest_prenatales')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo_control', 'con_4to_control')->sum('dentro');
            $pI = (int) DB::table('prest_partos')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('lugar', 'servicio')->sum('cantidad');
            $pD = (int) DB::table('prest_partos')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('lugar', 'domicilio')->sum('cantidad');
            $pP = (int) DB::table('prest_partos')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('atendido_por', 'like', 'partera%')->sum('cantidad');
            $vA = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'vitA_puerpera_unica')->sum('cantidad');
            $hP = (int) DB::table('prest_micronutrientes')->where('centro_salud_id', $cs->id)
                ->where('anio', $this->anio)->whereIn('mes', $this->meses)
                ->where('tipo', 'hierro_puerperas_completo')->sum('cantidad');
            $tP = $pI + $pD;
            $di = $c1 - $tP; // Diferencia: CPN 1er trim − Total Partos

            $rows[] = [
                mb_strtoupper($cs->nombre), $c1, $c4,
                $pI, $pD, $pP, $tP,
                $vA, $hP,
                $di === 0 ? '0' : ($di > 0 ? "+{$di}" : (string) $di),
            ];
            $tots[0] += $c1;  $tots[1] += $c4;
            $tots[2] += $pI;  $tots[3] += $pD;  $tots[4] += $pP; $tots[5] += $tP;
            $tots[6] += $vA;  $tots[7] += $hP;
        }

        // Fila total MUNICIPIO
        $tDif = $tots[0] - $tots[5];
        $rows[] = [
            ['Municipio ' . $this->municipioNombre, self::TEAL_DARK],
            [$tots[0], self::TEAL_DARK], [$tots[1], self::TEAL_DARK],
            [$tots[2], self::TEAL_DARK], [$tots[3], self::TEAL_DARK],
            [$tots[4], self::TEAL_DARK], [$tots[5], self::TEAL_DARK],
            [$tots[6], self::TEAL_DARK], [$tots[7], self::TEAL_DARK],
            [($tDif >= 0 ? "+{$tDif}" : (string) $tDif), self::TEAL_DARK],
        ];

        $this->colorGrid($slide, $headers, $rows, 10, 95, self::W - 20, 28, $hC, 155);
        $this->footer($slide);
    }

    // ── Cierre ──

    private function slideCierre(): void
    {
        $slide = $this->newSlide();
        $bg = new BackgroundColor();
        $bg->setColor(new Color('FF' . self::TEAL_DARK));
        $slide->setBackground($bg);

        // Top accent
        $this->rect($slide, 0, 0, self::W, 10, self::CYAN);

        // Large thank you
        $this->txt($slide, 'GRACIAS', 0, 140, self::W, 90, 60, self::WHITE, true, 'center');

        // Decorative line
        $slide->createLineShape(400, 240, self::W - 400, 240)
            ->getBorder()->setColor(new Color('FF' . self::CYAN))->setLineWidth(3);

        $this->txt($slide, "Municipio de {$this->municipioNombre}", 0, 270, self::W, 50, 30, self::WHITE, true, 'center');
        $this->txt($slide, $this->periodoLabel(), 0, 330, self::W, 40, 22, self::TEAL_LIGHT, false, 'center');

        // Info block
        $this->rect($slide, 250, 430, self::W - 500, 70, self::TEAL);
        $this->rect($slide, 250, 430, self::W - 500, 4, self::CYAN);
        $this->txt($slide, 'SIMUES — Sistema de Información Municipal de Establecimientos de Salud', 260, 440, self::W - 520, 25, 14, self::WHITE, true, 'center');
        $this->txt($slide, "{$this->redSalud} · Ministerio de Salud y Deportes · Bolivia", 260, 468, self::W - 520, 22, 12, self::TEAL_LIGHT, false, 'center');

        // Bottom accent
        $this->rect($slide, 0, self::H - 10, self::W, 10, self::CYAN);
    }
}

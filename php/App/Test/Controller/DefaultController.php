<?php
namespace App\Test\Controller;

use \App\Shared\Controller\RestrictedController;

class DefaultController extends RestrictedController {

	public function index() {
		return $this->unit();
	}

	public function unit($ns=false) {
		restore_error_handler();

		$what = _GET('what', 'unit');
		$root = DIR_ROOT;

		$output = \Test\AllTests::main('default', $ns);
		$content = $this->unitReportPrepare($output);

		//$xml_file = DIR_TMP.'/Reports/report.xml';
		//$xml = new Core\XML(file_get_contents($xml_file));

		return render('unit/results', array(
			'wide' => false,
			'refresh'  => '/unit',
			'content' => $content,
			'title_1' => 'Running all unit tests',
			'title_2' => 'Finished running all tests',
			'subtitle_1' => '',
			'subtitle_2' => '',
		));
	}

	public function coverage($url=false) {
		if ($url) {
			die($this->coverageReportProxy($url));
		}

		if (_GET('refresh')) {
			\Core\Framework::XDebug(!IS_LIVE);
			$output = \Test\AllTests::main('coverage');
		}

		$title = 'Coverage report';
		return render('unit/results', array(
			'wide' => true,
			'refresh'  => IS_LIVE? false: '/coverage?refresh=1',
			'content' => render('unit/coverage'),
			'title_1' => $title,
			'title_2' => $title,
			'subtitle_1' => '',
			'subtitle_2' => '',
		));
	}

	public function db() {
		$printer = new \Model\Printer();
		return $printer->CompareModelWithDatabase();
	}

	public function docs($url=false) {

		$url = join('/', func_get_args());

		if ($url) {
			die($this->docsProxy($url));
		}
		$title = 'Auto Docs';
		return render('docs/index', array(
			'wide' => true,
			'content' => render('docs/results'),
			'title_1' => $title,
			'title_2' => $title,
			'subtitle_1' => '',
			'subtitle_2' => '',
		));
	}

	private function docsProxy($page) {
		$path = DIR_TMP."/Docs/{$page}";
		$ext = substr($path, strrpos($path, '.')+1);
		content_type($ext);
		$data = file_get_contents($path);
		if ($ext != 'html') return $data;
		return $this->docsPagePrepare($data);
	}

	private function docsPagePrepare($html) {
		$s1 = 'cellpadding="0">';
		$s2 = 'height="3" alt=""></td></tr>';
		$p1 = strpos($html, $s1) + strlen($s1);
		$p2 = strpos($html, $s2) + strlen($s2);
		return substr($html, 0, $p1).substr($html, $p2);
	}

	private function coverageReportProxy($page) {
		$path = DIR_TMP."/Reports/coverage/{$page}";
		$ext = substr($path, strrpos($path, '.')+1);
		content_type($ext);
		$data = file_get_contents($path);
		if ($ext != 'html') return $data;
		return $this->coveragePagePrepare($data);
	}

	private function coveragePagePrepare($html) {
		$s1 = 'cellpadding="0">';
		$s2 = 'height="3" alt=""></td></tr>';
		$p1 = strpos($html, $s1) + strlen($s1);
		$p2 = strpos($html, $s2) + strlen($s2);
		$html = substr($html, 0, $p1).substr($html, $p2);

		$prefix = '/coverage/';
		return str($html)->replace(array(
			'src="' => "src=\"{$prefix}",
			'href="#' => "_href_=\"#",
			'href="' => "href=\"{$prefix}",
			'_href_="#' => "href=\"#",
		))->get();
	}

	private function unitReportPrepare($text) {
		$pos = strpos($text, "\nTime: ");
		$footer = substr($text, $pos);
		$text = substr($text, 0, $pos);

		$text = preg_replace('/[ ]+(test[A-Za-z]+)/', "\n\t\t\\1", $text);
		$text = preg_replace('/\n[ ]+([A-Z][a-z]+_[A-Z][a-z]+)/', "\n\t\\1", $text);
		$text = preg_replace('/\n[ ]+([A-Z][a-z]+)/', "\n\\1", $text);

		$pairs = array(
			"\n" => '',
			' '  => '',
		);

		$out = array();
		$regexp = '/\n([\n]*)([\t]*)([A-Za-z_]+)(\n(?:[ ]+[F\.]+)(?:\n(?:[ ]+[F\.]+))?)?/';
		preg_match_all($regexp, $text, $regs);
		foreach (arr($regs)->flip() as $match) {
			$newlines = $match[1];
			$tabs = $match[2];
			$num = 30 - strlen($newlines)*3;
			$name = sprintf("%-{$num}s", trim($match[3]));
			$dots = str($match[4])->replace($pairs)->trim()->get();
			$fail = (strpos($dots, 'F') !== false);
			$out[] = $fail?
				"<em>{$tabs}{$name} {$dots}</em>":
				"{$tabs}{$name} {$dots}";
		}
		$text = join("\n", $out);

		$text = $text;
		$text = str($text."\n".$footer)->replace(array(
			'\\' => '/',
			DIR_ROOT => '',
			"\t" => '   ',
		));
		return "<pre>{$text}</pre>";
	}

}
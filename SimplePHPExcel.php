<?php

require 'PHPExcel.php';

class SimplePHPExcel {

	public $excel;

	/* 行轴起始位置 */
	protected $startX = 2;
	/* 列轴起始位置 */
	protected $startY = 2;
	/* 总行数 */
	protected $totalRows;
	/* 总列数 */
	protected $totalColumns;

	/* 支持的导出类型 */
	const TYPE_CSV = 'CSV';
	const TYPE_PDF = 'PDF';
	const TYPE_HTML = 'HTML';
	const TYPE_EXCEL5 = 'Excel5';
	const TYPE_EXCEL7 = 'Excel2007';

	function SimplePHPExcel() {
		$this->excel = new PHPExcel();
	}

	/**
	 * 数组格式
	 * array(
	 * 	'u' => 'Creator',
	 *  'm' => 'LastModifiedBy',
	 *  't' => 'Title',
	 *  's' => 'Subject',
	 *  'd' => 'Description',
	 *  'k' => 'Keywords',
	 *  'c' => 'Category'
	 * )
	 *
	 * @param array $properties
	 */
	function setProperties(array $properties = array()) {
		if(!empty($properties)) {
			$this->excel->getProperties()->setCreator($properties['u'])
								   ->setLastModifiedBy($properties['m'])
								   ->setTitle($properties['t'])
								   ->setSubject($properties['s'])
								   ->setDescription($properties['d'])
								   ->setKeywords($properties['k'])
								   ->setCategory($properties['c']);
		}
	}

	/**
	 * 导出到文件
	 * type 支持Excel2007,Excel5,HTML,PDF,CSV
	 * target 完整保存路径，默认保存到和当前类同级的目录，文件名和类名相同
	 *
	 * @param string $type
	 * @param string $target
	 */
	function exportToFile($type, $target = null) {
		$suffix = $this->getSuffixByType($type);
		$writer = PHPExcel_IOFactory::createWriter($this->excel, $type);
		if(empty($target)) {
			$target = str_replace('.php', '.' . $suffix, __FILE__);
		}
		$writer->save($target);
	}

	/**
	 * 导出到浏览器
	 * type 支持Excel2007,Excel5,HTML,PDF,CSV
	 * filename 输出到浏览器的文件名
	 *
	 * @param string $type
	 * @param string $filename
	 */
	function exportToBrowser($type, $filename) {

		/* Excel2007和CSV默认使用同一种ContentType */
		$contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		switch ($type) {
			case self::TYPE_EXCEL5:
				$contentType = 'application/vnd.ms-excel';
				break;
			case self::TYPE_HTML:
				$contentType = 'text/html';
				break;
			case self::TYPE_PDF:
				$contentType = 'application/pdf';
				break;
		}
		$filename .= '.' . $this->getSuffixByType($type);

		$ua = $_SERVER["HTTP_USER_AGENT"];
		$encoded_filename = urlencode($filename);
		$encoded_filename = str_replace("+", "%20", $encoded_filename);

		header('Content-Type: ' . $contentType);
		if (preg_match("/MSIE/", $ua)) {
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}
		header('Cache-Control: max-age=0');

		ob_clean();

		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, $type);
		$objWriter->save('php://output');
	}

	/**
	 * 添加行
	 * 默认最大支持52列（含起始空白列）
	 *
	 * @param array $row
	 * @param integer $y
	 */
	function addRow(array $row, $y = 0) {
		$x = $this->startX + 65;
		$y = $y ? $y : $this->startY;
		foreach ($row as $item) {
			if($x <= 90) {
				$position = chr($x) . $y;
			}elseif($x > 90 && $x <= 126) {
				$position = "A" . chr($x - 26) . $y;
			}else {
				exit;
			}
			$this->excel->setActiveSheetIndex(0)
				 ->setCellValue($position, $item);
			$x++;
		}
	}

	/**
	 * 绘制表格
	 *
	 * @param array $titles
	 * @param array $bodys
	 */
	function drawTable(array $titles, array $bodys) {
		$this->totalRows = count($bodys);
		$this->totalColumns = count($titles);

		/* 添加头数据 */
		$this->addRow($titles, $this->startY + 1);

		/* 添加行数据 */
		$rowIndex = $this->startY + 2;
		foreach ($bodys as $row) {
			$this->addRow($row, $rowIndex++);
		}

		$styleHeader = array(
			'font' => array(
				'bold' => true,
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			),
			'borders' => array(
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
			),
		);
		$startCell = chr($this->startX + 65) . ($this->startY + 1);
		
		$lastCellIndex = $this->startX + 64 + $this->totalColumns;
		$lastCell = chr($lastCellIndex);
		if($lastCellIndex > 90 && $lastCellIndex <= 126) {
			$lastCell = "A" . chr($lastCellIndex - 26);
		}
		$headerColumn = "{$startCell}:{$lastCell}" . ($this->startY + 1);
		$this->excel->getActiveSheet()->getStyle($headerColumn)->applyFromArray($styleHeader);

		$styleOutline = array(
			'borders' => array(
				'outline' => array(
					'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				)
			)
		);
		$bodyColumn =  "{$startCell}:{$lastCell}" . ($this->totalRows + $this->startY + 1);
		$this->excel->getActiveSheet()->getStyle($bodyColumn)->applyFromArray($styleOutline);
	}

	/**
	 *
	 *
	 * @param integer $startx
	 */
	function setStartX($startx) {
		$this->startX = $startx;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $starty
	 */
	function setStartY($starty) {
		$this->startY = $starty;
	}

	/**
	 * 设置列样式
	 *
	 * @param array $styles
	 */
	function setCellStyle($styles) {
		$alignments = array(
			"left" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
			"right" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
			"center" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		);
		foreach ($styles as $k => $v) {
			$cells = $this->getCells($k);
			if(isset($v["bold"])) {
				$this->excel->getActiveSheet()->getStyle($cells)->getFont()->setBold($v["bold"]);
			}elseif (isset($v["align"])) {
				$this->excel->getActiveSheet()->getStyle($cells)->getAlignment()->setHorizontal($alignments[$v["align"]]);
			}elseif (isset($v["color"])){
				$this->excel->getActiveSheet()->getStyle($cells)->getFont()->getColor()->setARGB($v["color"]);
			}
		}
	}

	/**
	 * 获得单元格样式对象
	 *
	 * @param integer|string $colIndex
	 * @return string
	 */
	private function getCells($colIndex) {
		$col = $colIndex;
		/* 列索引的话转换为英文字符 */
		if(is_numeric($colIndex)) {
			$col = chr($colIndex + $this->startX + 64);
		}
		$startCell = $col . ($this->startY + 2);
		$lastCell = $col . ($this->totalRows + $this->startY + 1);
		return "{$startCell}:{$lastCell}";
	}

	/**
	 * 设置列宽
	 * 格式 B=20,C=30,D=20
	 *
	 * @param string $widthStr
	 */
	function setColumnWidthManual($widthStr) {
		$widths = explode(",", $widthStr);
		foreach ($widths as $width) {
			$part = explode("=", $width);
			$col = chr($part[0] + $this->startX + 64);
			$this->excel->getActiveSheet()->getColumnDimension($col)->setWidth($part[1]);
		}
	}

	/**
	 * 列自适应宽度
	 * 此方法不好用，建议手动设置列宽
	 * 或者配合自动换行使用
	 *
	 */
	function setColumnWidthAuto() {
		$colIndex = $this->startX + 65;
		for ($i = 0; $i < $this->totalColumns; $i++) {
			$this->excel->getActiveSheet()->getColumnDimension(chr($colIndex++))->setAutoSize(true);
		}
//		/* 自动换行 */
//		$this->excel->getActiveSheet()->getStyle("B2:$lastIndex")->getAlignment()->setWrapText(true);
	}


	/**
	 * 获取文件后缀
	 *
	 * @param string $type
	 * @return string
	 */
	function getSuffixByType($type) {
		$suffix = "";
		switch ($type) {
			case self::TYPE_EXCEL7:
				$suffix = 'xlsx';
				break;
			case self::TYPE_EXCEL5:
				$suffix = 'xls';
				break;
			case self::TYPE_HTML:
				$suffix = 'html';
				break;
			case self::TYPE_PDF:
				$suffix = 'pdf';
				break;
			case self::TYPE_CSV:
				$suffix = 'csv';
				break;
		}
		return $suffix;
	}

}

?>

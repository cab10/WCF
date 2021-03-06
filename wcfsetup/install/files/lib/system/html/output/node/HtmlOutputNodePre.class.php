<?php
namespace wcf\system\html\output\node;
use wcf\system\bbcode\highlighter\BashHighlighter;
use wcf\system\bbcode\highlighter\CHighlighter;
use wcf\system\bbcode\highlighter\DiffHighlighter;
use wcf\system\bbcode\highlighter\HtmlHighlighter;
use wcf\system\bbcode\highlighter\JavaHighlighter;
use wcf\system\bbcode\highlighter\JsHighlighter;
use wcf\system\bbcode\highlighter\PerlHighlighter;
use wcf\system\bbcode\highlighter\PhpHighlighter;
use wcf\system\bbcode\highlighter\PlainHighlighter;
use wcf\system\bbcode\highlighter\PythonHighlighter;
use wcf\system\bbcode\highlighter\SqlHighlighter;
use wcf\system\bbcode\highlighter\TexHighlighter;
use wcf\system\bbcode\highlighter\XmlHighlighter;
use wcf\system\html\node\AbstractHtmlNodeProcessor;
use wcf\system\Regex;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Processes code listings.
 * 
 * @author      Alexander Ebert
 * @copyright   2001-2017 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     WoltLabSuite\Core\System\Html\Output\Node
 * @since       3.0
 */
class HtmlOutputNodePre extends AbstractHtmlOutputNode {
	/**
	 * @inheritDoc
	 */
	protected $tagName = 'pre';
	
	/**
	 * already used ids for line numbers to prevent duplicate ids in the output
	 * @var	string[]
	 */
	private static $codeIDs = [];
	
	/**
	 * @inheritDoc
	 */
	public function process(array $elements, AbstractHtmlNodeProcessor $htmlNodeProcessor) {
		/** @var \DOMElement $element */
		foreach ($elements as $element) {
			if ($element->getAttribute('class') === 'woltlabHtml') {
				$nodeIdentifier = StringUtil::getRandomID();
				$htmlNodeProcessor->addNodeData($this, $nodeIdentifier, ['rawHTML' => $element->textContent]);
				
				$htmlNodeProcessor->renameTag($element, 'wcfNode-' . $nodeIdentifier);
				continue;
			}
			
			switch ($this->outputType) {
				case 'text/html':
					$nodeIdentifier = StringUtil::getRandomID();
					$htmlNodeProcessor->addNodeData($this, $nodeIdentifier, [
						'content' => $element->textContent,
						'file' => $element->getAttribute('data-file'),
						'highlighter' => $element->getAttribute('data-highlighter'),
						'line' => $element->hasAttribute('data-line') ? $element->getAttribute('data-line') : 1
					]);
					
					$htmlNodeProcessor->renameTag($element, 'wcfNode-' . $nodeIdentifier);
					break;
				
				case 'text/simplified-html':
				case 'text/plain':
					$htmlNodeProcessor->replaceElementWithText(
						$element,
						WCF::getLanguage()->getDynamicVariable('wcf.bbcode.code.simplified', ['lines' => substr_count($element->nodeValue, "\n") + 1]),
						true
					);
					break;
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function replaceTag(array $data) {
		// HTML bbcode
		if (isset($data['rawHTML'])) {
			return $data['rawHTML'];
		}
		
		$content = preg_replace('/^\s*\n/', '', $data['content']);
		$content = preg_replace('/\n\s*$/', '', $content);
		
		$file = $data['file'];
		$highlighter = $data['highlighter'];
		$line = ($data['line'] < 1) ? 1 : $data['line'];
		
		// fetch highlighter-classname
		$className = PlainHighlighter::class;
		
		// no highlighting for strings over a certain size, to prevent DoS
		// this serves as a safety net in case one of the regular expressions
		// in a highlighter causes PCRE to exhaust resources, such as the stack
		if (strlen($content) < 16384) {
			if ($highlighter) {
				$className = '\wcf\system\bbcode\highlighter\\'.StringUtil::firstCharToUpperCase(mb_strtolower($highlighter)).'Highlighter';
				
				switch (mb_substr($className, strlen('\wcf\system\bbcode\highlighter\\'))) {
					case 'ShellHighlighter':
						$className = BashHighlighter::class;
						break;
					
					case 'C++Highlighter':
						$className = CHighlighter::class;
						break;
					
					case 'JavascriptHighlighter':
						$className = JsHighlighter::class;
						break;
					
					case 'LatexHighlighter':
						$className = TexHighlighter::class;
						break;
				}
			}
			else {
				// try to guess highlighter
				if (mb_strpos($content, '<?php') !== false) {
					$className = PhpHighlighter::class;
				}
				else if (mb_strpos($content, '<html') !== false) {
					$className = HtmlHighlighter::class;
				}
				else if (mb_strpos($content, '<?xml') === 0) {
					$className = XmlHighlighter::class;
				}
				else if (	mb_strpos($content, 'SELECT') === 0
					||	mb_strpos($content, 'UPDATE') === 0
					||	mb_strpos($content, 'INSERT') === 0
					||	mb_strpos($content, 'DELETE') === 0) {
					$className = SqlHighlighter::class;
				}
				else if (mb_strpos($content, 'import java.') !== false) {
					$className = JavaHighlighter::class;
				}
				else if (	mb_strpos($content, "---") !== false
					&&	mb_strpos($content, "\n+++") !== false) {
					$className = DiffHighlighter::class;
				}
				else if (mb_strpos($content, "\n#include ") !== false) {
					$className = CHighlighter::class;
				}
				else if (mb_strpos($content, '#!/usr/bin/perl') === 0) {
					$className = PerlHighlighter::class;
				}
				else if (mb_strpos($content, 'def __init__(self') !== false) {
					$className = PythonHighlighter::class;
				}
				else if (Regex::compile('^#!/bin/(ba|z)?sh')->match($content)) {
					$className = BashHighlighter::class;
				}
				else if (mb_strpos($content, '\\documentclass') !== false) {
					$className = TexHighlighter::class;
				}
			}
		}
		
		if (!class_exists($className)) {
			$className = PlainHighlighter::class;
		}
		
		/** @noinspection PhpUndefinedMethodInspection */
		$highlightedContent = $this->fixMarkup(explode("\n", $className::getInstance()->highlight($content)));
		
		// show template
		/** @noinspection PhpUndefinedMethodInspection */
		WCF::getTPL()->assign([
			'lineNumbers' => $this->makeLineNumbers($content, $line),
			'startLineNumber' => $line,
			'content' => $highlightedContent,
			'highlighter' => $className::getInstance(),
			'filename' => $file,
			'lines' => substr_count($content, "\n") + 1
		]);
		
		return WCF::getTPL()->fetch('codeMetaCode');
	}
	
	/**
	 * Returns a string with all line numbers
	 *
	 * @param	string		$code
	 * @param	integer		$start
	 * @param	string		$split
	 * @return	string[]
	 */
	protected function makeLineNumbers($code, $start, $split = "\n") {
		$lines = explode($split, $code);
		
		$lineNumbers = [];
		$i = -1;
		// find an unused codeID
		do {
			$codeID = mb_substr(StringUtil::getHash($code), 0, 6).(++$i ? '_'.$i : '');
		}
		while (isset(self::$codeIDs[$codeID]));
		
		// mark codeID as used
		self::$codeIDs[$codeID] = true;
		
		for ($i = $start, $j = count($lines) + $start; $i < $j; $i++) {
			$lineNumbers[$i] = 'codeLine_'.$i.'_'.$codeID;
		}
		return $lineNumbers;
	}
	
	/**
	 * Fixes markup that every line has proper number of opening and closing tags
	 *
	 * @param	string[]	$lines
	 * @return	string[]
	 */
	protected function fixMarkup(array $lines) {
		static $spanRegex = null;
		static $emptyTagRegex = null;
		if ($spanRegex === null) {
			$spanRegex = new Regex('(?:<span(?: class="(?:[^"])*")?>|</span>)');
			$emptyTagRegex = new Regex('<span(?: class="(?:[^"])*")?></span>');
		}
		
		$openTags = [];
		foreach ($lines as &$line) {
			$spanRegex->match($line, true);
			// open all tags again
			$line = implode('', $openTags).$line;
			$matches = $spanRegex->getMatches();
			
			// parse opening and closing spans
			foreach ($matches[0] as $match) {
				if ($match === '</span>') array_pop($openTags);
				else {
					array_push($openTags, $match);
				}
			}
			
			// close all tags
			$line .= str_repeat('</span>', count($openTags));
			
			// remove empty tags to avoid cluttering the output
			$line = $emptyTagRegex->replace($line, '');
		}
		unset($line);
		
		return $lines;
	}
}

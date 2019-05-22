<?php

namespace MWStew\CLI;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class MWStewBaseCommand extends Command {
	protected $styles = [];

	/** @var string Full filesystem path to the extensions directory. */
	protected static $extensionsDir;

	public function __construct( string $name = null ) {
		parent::__construct( $name );

		$this->styles = [
			'mw' => new OutputFormatterStyle( 'yellow', 'black', [ 'bold' ] ),
			'code' => new OutputFormatterStyle( 'green', 'black' ),
			'stop' => new OutputFormatterStyle( 'red', 'black', [ 'bold' ] ),
			'error' => new OutputFormatterStyle( 'red' ),
			'working' => new OutputFormatterStyle( 'green', 'default', [ 'bold' ] ),
			'finished' => new OutputFormatterStyle( 'green', 'black', [ 'bold' ] ),
			'hi' => new OutputFormatterStyle( 'black', 'green', [ 'bold' ] ),
		];
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		// Register all styles
		foreach ( $this->styles as $styleName => $styleObject ) {
			$output->getFormatter()->setStyle( $styleName, $styleObject );
		}
	}

	protected function getOutputHeader() {
		return array_merge(
			[ '' ],
			$this->getMediaWikiAscii( 'mw' ),
			$this->getMWStewAscii(),
			[ '<mw>          =*=*= MediaWiki extension maker =*=*=                   </>' ],
			[ '' ]
		);
	}

	protected function outError( $str = '' ) {
		return [
			'<stop>ERROR.</> <error>' . $str . '</>',
			''
		];
	}

	protected function getMediaWikiAscii( $style = null ) {
		$ascii = [
			'          __  __          _ _    __          ___ _    _           ',
			'         |  \/  |        | (_)   \ \        / (_) |  (_)          ',
			'         | \  / | ___  __| |_  __ \ \  /\  / / _| | ___           ',
			'         | |\/| |/ _ \/ _` | |/ _` \ \/  \/ / | | |/ / |          ',
			'         | |  | |  __/ (_| | | (_| |\  /\  /  | |   <| |          ',
			'         |_|  |_|\___|\__,_|_|\__,_| \/  \/   |_|_|\_\_|          ',
		];

		return $this->addStyleToArray( $ascii, $style );
	}

	protected function getMWStewAscii( $style = null ) {
		$ascii = [
			'     ███╗   ███╗██╗    ██╗███████╗████████╗███████╗██╗    ██╗   ',
			'     ████╗ ████║██║    ██║██╔════╝╚══██╔══╝██╔════╝██║    ██║   ',
			'     ██╔████╔██║██║ █╗ ██║███████╗   ██║   █████╗  ██║ █╗ ██║   ',
			'     ██║╚██╔╝██║██║███╗██║╚════██║   ██║   ██╔══╝  ██║███╗██║   ',
			'     ██║ ╚═╝ ██║╚███╔███╔╝███████║   ██║   ███████╗╚███╔███╔╝   ',
			'     ╚═╝     ╚═╝ ╚══╝╚══╝ ╚══════╝   ╚═╝   ╚══════╝ ╚══╝╚══╝    ',
		];
		return $this->addStyleToArray( $ascii, $style );
	}

	protected function addStyleToArray( $ascii = [], $style = null ) {
		if ( $style ) {
			$new = [];
			foreach ( $ascii as $a ) {
				$new[] = '<' . $style . '>' . $a . '</>';
			}
			return $new;
		}
		return $ascii;
	}

	/**
	 * Get the full filesystem path to the extensions directory, first looking down a level from the current working
	 * directory, and then stepping up through the directories to see if it exists.
	 * @return string
	 * @throws Exception If no extensions directory can be determined.
	 */
	protected function getExtensionsDir()
	{
		if (static::$extensionsDir) {
			return static::$extensionsDir;
		}

		$cwd = getcwd();

		// Look down one level.
		if (is_dir("$cwd/extensions")) {
			$extDir = "$cwd/extensions";

		} else {
			// Iterate upwards, checking two things at each step.
			$dir = $cwd;
			do {
				// Drop the last component of the path.
				$dir = substr($dir, 0, strrpos($dir, '/'));
				// See if it's an extensions directory.
				if (basename($dir) === 'extensions') {
					$extDir = $dir;
					break;
				}
				// See if there's an extensions directory in it.
				if (is_dir("$dir/extensions")) {
					$extDir = "$dir/extensions";
					break;
				}
			} while ($dir);
		}

		// Complain if nothing was found.
		if (!isset($extDir)) {
			throw new Exception("Unable to find extensions directory from $cwd");
		}

		// Return the full path to the extensions directory.
		static::$extensionsDir = realpath($extDir);
		return static::$extensionsDir;
	}
}

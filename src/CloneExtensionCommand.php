<?php

namespace MWStew\CLI;

use function Couchbase\defaultDecoder;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CloneExtensionCommand extends MWStewBaseCommand
{

	protected function configure()
	{
		$this->setName('clone-extension');
		$this->setDescription("Clone an extension's Git repository. Only supports Gerrit repos at the moment.");
		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'Name of the extension. Alphabet or numbers only, no spaces.'
		);
		$this->addOption(
			'path',
			'p',
			InputOption::VALUE_REQUIRED,
			'The path for the new files.',
			$this->getExtensionsDir()
		);
		$this->addOption(
			'user',
			'u',
			InputOption::VALUE_REQUIRED,
			'Your Gerrit username.'
		);
		$this->addOption(
			'githook',
			'g',
			InputOption::VALUE_NONE,
			'Whether to add the Gerrit commit-msg hook. Requires --user.'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		$io = new SymfonyStyle($input, $output);
		$path = $input->getOption('path');
		$name = $input->getArgument('name');

		// Figure out the Git repository URL.
		$repoUrl = "https://gerrit.wikimedia.org/r/mediawiki/extensions/$name";
		$user = $input->getOption('user');
		if ($user) {
			$repoUrl = "ssh://$user@gerrit.wikimedia.org:29418/mediawiki/extensions/$name";
		}

		// Run the Git command.
		$command = ['git', 'clone', '--origin', 'gerrit', $repoUrl, "$path/$name"];
		$process = new Process($command);
		$process->mustRun();
		$io->success("$name has been cloned to $path/$name");

		// See if we should copy the Git hook as well.
		$useHook = $input->getOption('githook');
		if ($useHook && !$user) {
			$io->error('The --githook option can only be used in conjunction with the --user option.');
			return 1;
		}
		if ($useHook) {
			$command = [
				'scp',
				'-p',
				'-P',
				'29418',
				"$user@gerrit.wikimedia.org:hooks/commit-msg",
				"$path/$name/.git/hooks/"
			];
			$process = new Process($command);
			$process->mustRun();
			$io->success("The Gerrit commit-msg hook has been installed.");
		}
	}
}

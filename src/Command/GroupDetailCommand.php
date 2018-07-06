<?php declare(strict_types=1);

namespace Fop\Command;

use Fop\Api\MeetupComApi;
use Fop\Country\CountryResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class GroupDetailCommand extends Command
{
    /**
     * @var string
     */
    private const ARGUMENT_GROUP_URL = 'group-url';

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var MeetupComApi
     */
    private $meetupComApi;

    /**
     * @var CountryResolver
     */
    private $countryResolver;

    public function __construct(
        MeetupComApi $meetupComApi,
        SymfonyStyle $symfonyStyle,
        CountryResolver $countryResolver
    ) {
        parent::__construct();
        $this->symfonyStyle = $symfonyStyle;
        $this->meetupComApi = $meetupComApi;
        $this->countryResolver = $countryResolver;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->addArgument(
            self::ARGUMENT_GROUP_URL,
            InputArgument::REQUIRED,
            'Group url on meetup.com, e.g. https://www.meetup.com/Berlin-PHP-Usergroup/'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $group = $this->meetupComApi->getGroupForUrl((string) $input->getArgument(self::ARGUMENT_GROUP_URL));

        $country = $this->countryResolver->resolveFromGroup($group);

        $this->symfonyStyle->writeln(sprintf("name: '%s'", $group['name']));
        $this->symfonyStyle->writeln(sprintf('meetup_com_id: %s', $group['id']));
        $this->symfonyStyle->writeln(sprintf("meetup_com_url: '%s'", $group['link']));
        $this->symfonyStyle->writeln(sprintf("country: '%s'", ($country ? $country->getName() : 'unknown')));
    }
}
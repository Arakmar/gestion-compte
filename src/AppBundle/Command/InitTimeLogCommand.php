<?php
// src/AppBundle/Command/InitTimeLogCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\Shift;
use AppBundle\Entity\TimeLog;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitTimeLogCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:user:init_time_log')
            ->setDescription('Init time logs data')
            ->setHelp('This command allows you to init time logs data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $countShiftLogs = 0;
        $countCycleBeginning = 0;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $users = $em->getRepository('AppBundle:User')->findAll();
        $beginningOfLastCycle = new \DateTime('28 days ago');
        $beginningOfLastCycle->setTime(0, 0, 0);
        foreach ($users as $user) {
            if ($user->getFirstShiftDate()) {

                $lastCycleShifts = $user->getShiftsOfCycle(-1, true)->toArray();
                $currentCycleShifts = $user->getShiftsOfCycle(0, true)->toArray();
                $shifts = array_merge($lastCycleShifts, $currentCycleShifts);
                foreach ($shifts as $shift) {
                    $this->createShiftLog($em, $shift, $user);
                    $countShiftLogs++;
                }

                if ($user->getFirstShiftDate() < $beginningOfLastCycle) {
                    $this->createCurrentCycleBeginningLog($em, $user);
                    $countCycleBeginning++;
                }
            }
        }
        $em->flush();
        $output->writeln($countShiftLogs . ' logs de créneaux réalisés créés');
        $output->writeln($countCycleBeginning . ' logs de début de cycle créés');
    }

    private function createShiftLog(EntityManager $em, Shift $shift, User $user)
    {
        $log = new TimeLog();
        $log->setUser($user);
        $log->setTime($shift->getDuration());
        $log->setShift($shift);
        $log->setDate($shift->getStart());
        $log->setDescription("Créneau réalisé");
        $em->persist($log);
    }

    private function createCurrentCycleBeginningLog(EntityManager $em, User $user)
    {
        $date = $user->startOfCycle(0);
        $log = new TimeLog();
        $log->setUser($user);
        $log->setTime(-180);
        $log->setDate($date);
        $log->setDescription("Début de cycle");
        $em->persist($log);
    }

}
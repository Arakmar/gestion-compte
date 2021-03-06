<?php
// src/AppBundle/Command/FixTimeLogCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\Shift;
use AppBundle\Entity\TimeLog;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixTimeLogCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:user:fix_time_log')
            ->setDescription('Fix time logs data')
            ->setHelp('This command allows you to fix time logs data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $countShiftLogs = 0;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $users = $em->getRepository('AppBundle:User')->findAll();
        foreach ($users as $user) {
            if ($user->getFirstShiftDate()) {

                $lastCycleShifts = $user->getShiftsOfCycle(-1, true)->toArray();
                $currentCycleShifts = $user->getShiftsOfCycle(0, true)->toArray();
                $shifts = array_merge($lastCycleShifts, $currentCycleShifts);
                foreach ($shifts as $shift) {

                    $logs = $user->getTimeLogs()->filter(function ($log) use ($shift) {
                        return ($log->getShift() && $log->getShift()->getId() == $shift->getId());
                    });

                    // Insert log if it doesn't exist fot this shift
                    if ($logs->count() == 0) {
                        $this->createShiftLog($em, $shift, $user);
                        $countShiftLogs++;
                    }
                }
            }
        }
        $em->flush();
        $output->writeln($countShiftLogs . ' logs de créneaux réalisés créés');
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

}
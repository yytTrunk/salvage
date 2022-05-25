<?php
declare (strict_types = 1);

namespace app\program\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Common extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('common')
            ->setDescription('the common command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('common');
    }
}

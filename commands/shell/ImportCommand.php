<?php

class ImportCommand extends CConsoleCommand
{
    public function init()
    {
        
    }

    public function actionIndex() {
        new c2wImport();
    }
}

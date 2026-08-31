<?php
dump(DB::table('activity_log')->latest('id')->first());

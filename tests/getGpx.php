<?php

// https://github.com/pe-st/garmin-connect-export/blob/master/gcexport.py#L773

/*
    URL_GC_GPX_ACTIVITY = f'{GARMIN_BASE_URL}/download-service/export/gpx/activity/'
    data_filename = os.path.join(directory, f'{prefix}activity_{activity_id}{append_desc}.gpx')
    download_url = f'{URL_GC_GPX_ACTIVITY}{activity_id}?full=true'
    file_mode = 'w'
*/

// Step 1: Login if no token available
// Step 2: Request GPX File
// Step 3: Parse file and gather metadata
// Step 4: Store data (as JSON) to database

// ToDo create cron to fill database with historic data

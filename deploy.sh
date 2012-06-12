#!/bin/bash

find mod_views25 ! -path "*.svn*" ! -path "*nbproject*" ! -name ".DS_Store" -print | zip mod_views25 -@
find plg_system_views25 ! -path "*.svn*" ! -path "*nbproject*" ! -name ".DS_Store" -print | zip plg_system_views25 -@

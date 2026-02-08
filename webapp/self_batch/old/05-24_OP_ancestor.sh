# {--mode=ALL : ANCESTOR|INBREED|ALL}
# {--jyo= : 競馬場コード（例: 05）。未指定なら全場}
# {--from= : 期間開始 (YYYY or YYYY-MM-DD)}
# {--to= : 期間終了 (YYYY or YYYY-MM-DD)}
# {--excludeYears= : 除外したい年（例：2021,2022）}
# {--grade=ALL : ALL|G1|G2|G3|GRADE|OP|COND}
# {--ancestor_mode=ALL : ALL|F|M|FM}

#!/bin/bash
set -e  # エラー時に停止する場合は有効にする

php -d memory_limit=512M artisan course:analyze --jyo=01 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=02 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=03 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=04 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=05 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=06 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=07 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=08 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=09 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=10 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=ALL --grade=OP

php -d memory_limit=512M artisan course:analyze --jyo=01 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=02 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=03 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=04 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=05 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=06 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=07 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=08 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=09 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=10 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=F --grade=OP

php -d memory_limit=512M artisan course:analyze --jyo=01 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=02 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=03 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=04 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=05 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=06 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=07 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=08 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=09 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP
php -d memory_limit=512M artisan course:analyze --jyo=10 --from=2005 --to=2024 --mode=ANCESTOR --ancestor_mode=M --grade=OP

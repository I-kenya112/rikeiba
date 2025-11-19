#!/bin/bash
set -e  # エラー時に停止する場合は有効にする

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=G1 --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=GRADE --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=OP --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=ANCESTOR --grade=ALL --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-1400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-1600 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-1800 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-2000 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-2300 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-2400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-2500 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-TURF-3400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-DIRT-1300 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-DIRT-1400 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-DIRT-1600 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-DIRT-2100 --limitYears=21 --excludeCurrentYear

php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=G1 --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=GRADE --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=OP --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear
php -d memory_limit=512M artisan course:analyze --mode=INBREED --grade=ALL --course=05-DIRT-2400 --limitYears=21 --excludeCurrentYear

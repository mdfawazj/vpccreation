<?php

// for 7400: ./test.php status_staging 7400
$pstr["status_staging"] = "merchant_number=99032809997&MID=99032809997&TID=00000000000&DID=00010743194075661445&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
// for 7400: ./test.php status_production 7400
$pstr["status_production"] = "merchant_number=99032809997&MID=99032809997&TID=00000000000&DID=00017313520045460248&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Walmart South Africa (test, 22 Jun 2023 - https://enterprise-jira.onefiserv.net/browse/GGS-1033
# ./test.php test_walmart_south_africa 2010 1
$pstr["test_walmart_south_africa"] = "merchant_number=99022119997&MID=99022119997&TID=00000000001&DID=00054237931886135332&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=710";

# City BBQ (prod, 8 Aug 2023 - https://enterprise-jira.onefiserv.net/browse/GGS-1670)
# ./test.php prod_city_bbq 2010 1
$pstr["prod_city_bbq"] = "merchant_number=99022249996&MID=99022249996&TID=00000000001&DID=00211287802188031359&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# City BBQ (test, 30 Mar 2023 - https://firstdatateam.atlassian.net/browse/ENG-7028)
# ./test.php test_city_bbq 2010 1
$pstr["test_city_bbq"] = "merchant_number=99022249996&MID=99022249996&TID=00000000001&DID=00052916889522941960&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# MAD Greens (PROD, 21 Mar 2023 - https://firstdatateam.atlassian.net/browse/ENG-6996)
# ./test.php prod_mad_greens 2010 1
$pstr["prod_mad_greens"] = "merchant_number=99022499995&MID=99022499995&TID=00000000001&DID=00197502893263704994&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Costco AU - Transaction Wireless Australia FDC TPTS (PROD, 26 Apr 2023 - https://firstdatateam.atlassian.net/browse/ENG-7440)
# ./test.php prod_costco_au 2010 1
$pstr["prod_costco_au"] = "merchant_number=99024009996&MID=99024009996&TID=00000000001&DID=00051528898477784180&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=36&alternate_merchant_number=0001";

# Costco AU - Transaction Wireless Australia FDC TPTS (TEST, 6 Jan 2023 - https://firstdatateam.atlassian.net/browse/ENG-6204)
# ./test.php test_costco_au 2010 1
$pstr["test_costco_au"] = "merchant_number=99024009996&MID=99024009996&TID=00000000001&DID=00051528898477784180&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=36&alternate_merchant_number=0001";

# Costco NZ - TransactionWireless New Zealand FDC TPTS (TEST, 6 Jan 2023 - https://firstdatateam.atlassian.net/browse/ENG-6204)
# ./test.php test_costco_nz 2010 1
$pstr["test_costco_nz"] = "merchant_number=99022419997&MID=99022419997&TID=00000000001&DID=00051528910642487901&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=554&alternate_merchant_number=0001";

# Mellow Mushroom TransWireless Inet SMID (prod, 7 Nov 2022 - https://firstdatateam.atlassian.net/browse/ENG-5785)
# ./test.php prod_mellow_mushroom_smid 2010 1
$pstr["prod_mellow_mushroom_smid"] = "merchant_number=99022469996&MID=99022469996&TID=00000009999&DID=00184271247095532349&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# Mellow Mushroom TransWireless Inet SMID (test, 14 Oct 2022 - https://firstdatateam.atlassian.net/browse/ENG-5785)
# ./test.php test_mellow_mushroom_smid 2010 1
$pstr["test_mellow_mushroom_smid"] = "merchant_number=99022469996&MID=99022469996&TID=00000000000&DID=00050726408289206585&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# Hudson Bay Canada MID (PROD, 1 Nov 2021 - https://firstdatateam.atlassian.net/browse/ENG-2756)
# ./test.php prod_hudson_bay_canada 2010 1
$pstr["prod_hudson_bay_canada"] = "merchant_number=99023119995&MID=99023119995&TID=00000000001&DID=00149783361129725064&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=124";

# Union Square SMID (PROD, 12 Oct 2021 - https://firstdatateam.atlassian.net/browse/ENG-2950)
# ./test.php prod_union_square 2010 1
$pstr["prod_union_square"] = "merchant_number=99022959997&MID=99022959997&TID=00000000001&DID=00149783340257930681&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&alternate_merchant_number=0001";

# Raising Cane’s SMID (prod, 12 Oct 2021 - https://firstdatateam.atlassian.net/browse/ENG-2944)
# ./test.php prod_raising_cane 2010 1
$pstr["prod_raising_cane"] = "merchant_number=97487310027&MID=97487310027&TID=00000005432&DID=00149783311052019504&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&alternate_merchant_number=5432";

# Union Square SMID (test, 17 Sep 2021 - https://firstdatateam.atlassian.net/browse/ENG-2950)
# ./test.php test_union_square 2010 1
$pstr["test_union_square"] = "merchant_number=99022959997&MID=99022959997&TID=00000000001&DID=00042876920478168531&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&alternate_merchant_number=0001";

# Raising Cane’s SMID (test, 15 Sep 2021 - https://firstdatateam.atlassian.net/browse/ENG-2944)
# ./test.php test_raising_cane 2010 1
$pstr["test_raising_cane"] = "merchant_number=97487310027&MID=97487310027&TID=00000000001&DID=00042869359266483336&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&alternate_merchant_number=0001";

# Hudsons Bay TransWireless Can SMID (test, 1 Sep 2021 - https://firstdatateam.atlassian.net/browse/ENG-2756)
# ./test.php test_hudsons_bay_can 2010 1
$pstr["test_hudsons_bay_can"] = "merchant_number=99023119995&MID=99023119995&TID=00000000001&DID=00042802201351982170&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&currency_code=CAD";

# Cub (prod, 12 Aug 2021)
# ./test.php prod_cub 2010 1
$pstr["prod_cub"] = "merchant_number=97483490104&MID=97483490104&TID=00000002806&DID=00072669744703496615&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# Cub (test, 26 July 2021)
# ./test.php test_cub 2010 1
$pstr["test_cub"] = "merchant_number=97483490104&MID=97483490104&TID=00000002806&DID=00028279396303994689&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

# TW Panera B2C API FT12 SVS (prod, 22 Jul 2021 - https://firstdatateam.atlassian.net/browse/ENG-2125)
# ./test.php prod_tw_panera_b2c_api_ft12_svs 2010 1
$pstr["prod_tw_panera_b2c_api_ft12_svs"] = "merchant_number=99024219992&MID=99024219992&TID=00000609996&DID=00143617605984987892&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=3296043&source_code=30";

# TW Panera 20Pct Disc B2C API FT12 SVS (prod, 22 Jul 2021 - https://firstdatateam.atlassian.net/browse/ENG-2125)
# ./test.php prod_tw_panera_20pct_disc_b2c_api_ft12_svs 2010 1
$pstr["prod_tw_panera_20pct_disc_b2c_api_ft12_svs"] = "merchant_number=99024219993&MID=99024219993&TID=00000609997&DID=00143617629135709860&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=3296043&source_code=30";

# TW Panera B2C API FT12 SVS (test, 1 Jun 2021) ./test.php test_tw_panera_b2c_api_ft12_svs 2010 1
$pstr["test_tw_panera_b2c_api_ft12_svs"] = "merchant_number=99024219992&MID=99024219992&TID=00000000001&DID=00041782049225958566&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=36270&source_code=30";

# TW Panera 20Pct Disc B2C API FT12 SVS (test, 1 Jun 2021) ./test.php test_tw_panera_20pct_disc_b2c_api_ft12_svs 2010 1
$pstr["test_tw_panera_20pct_disc_b2c_api_ft12_svs"] = "merchant_number=99024219993&MID=99024219993&TID=00000000001&DID=00041782051722278578&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=36270&source_code=30";

# Panera Bread Bucks SMID (prod, 16 Mar 2021) ./test.php prod_panera_bread_bucks_smid 2010 1
$pstr["prod_panera_bread_bucks_smid"] = "merchant_number=99908319997&MID=99908319997&TID=00000100110&DID=00136337617688797179&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera Bread Bucks SMID (test, 9 Mar 2021) ./test.php test_panera_bread_bucks_smid 2010 1
$pstr["test_panera_bread_bucks_smid"] = "merchant_number=99908319997&MID=99908319997&TID=00000100110&DID=00040883938283243606&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera Bread Bucks Program (test, 23 Feb 2021) ./test.php test_panera_bread_bucks 2010 1
$pstr["test_panera_bread_bucks"] = "merchant_number=97555600911&MID=97555600911&TID=00000100110&DID=???&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promo=36270";

# Total Wine (prod, 11 Feb 2021) ./test.php prod_total_wine 2010 1
$pstr["prod_total_wine"] = "merchant_number=99908379997&MID=99908379997&TID=00000000001&DID=00134854666781575062&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Total Wine (test, 26 Jan 2021) ./test.php test_total_wine 2010 1
$pstr["test_total_wine"] = "merchant_number=99908379997&MID=99908379997&TID=00000000001&DID=00040319407569668468&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promo=45201";

# Balance Merge (2420 + 2806) cert testing, all the bmX_ cases below (test, 9 Dec 2020)

# Transaction Wireless FDC TPTS (xls line 2)
# EAN 11399:   ./test.php bm2_30 2420 "7777245678186789" "28474584" "7777245678197992" "02788755" ""
# SCV8 36046:  ./test.php bm2_31 2420 "7777245678289397" "91047531" "7777245678295477" "73079978" ""
# SCV4 36046:  ./test.php bm2_31 2420 "7777245678382408" "7585" "7777245678399662" "9875" ""
# no pin:      ./test.php bm2_31 2420 "7777245678186789" "" "7777245678197992" "" ""
$pstr["bm2"] = "merchant_number=99032809997&MID=99032809997&TID=00000000000&DID=00010743194075661445&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm2_30"] = $pstr["bm2"]."&currency_code=USD&source_code=30&promo=11399";
$pstr["bm2_31"] = $pstr["bm2"]."&currency_code=USD&source_code=31&promo=36046";

# Transaction Wireless US FT12 (xls line 3)
# EAN 11399:   ./test.php bm3_30 2420 "7777245678186789" "28474584" "7777245678197992" "02788755" ""
# SCV8 36046:  ./test.php bm3_31 2420 "7777245678289397" "91047531" "7777245678295477" "73079978" ""
# SCV4 36046:  ./test.php bm3_31 2420 "7777245678382408" "7585" "7777245678399662" "9875" ""
# no pin:      ./test.php bm3_31 2420 "7777245678186789" "" "7777245678197992" "" ""
$pstr["bm3"] = "merchant_number=99024909997&MID=99024909997&TID=00000000000&DID=00032129590277131005&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm3_30"] = $pstr["bm3"]."&currency_code=USD&source_code=30&promo=11399";
$pstr["bm3_31"] = $pstr["bm3"]."&currency_code=USD&source_code=31&promo=36046";

# Transaction Wireless ACE SMID Inet (xls line 4)
# EAN 11399:   ./test.php bm4_30 2420 "7777245678186789" "28474584" "7777245678197992" "02788755" ""
# SCV8 36046:  ./test.php bm4_31 2420 "7777245678289397" "91047531" "7777245678295477" "73079978" ""
# SCV4 36046:  ./test.php bm4_31 2420 "7777245678382408" "7585" "7777245678399662" "9875" ""
# no pin:      ./test.php bm4_31 2420 "7777245678186789" "" "7777245678197992" "" ""
$pstr["bm4"] = "merchant_number=99024129997&MID=99024129997&TID=00000000001&DID=00034994815858027261&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm4_30"] = $pstr["bm4"]."&currency_code=USD&source_code=30&promo=11399";
$pstr["bm4_31"] = $pstr["bm4"]."&currency_code=USD&source_code=31&promo=36046";

# Transaction Wireless GBP 826 TPTS (xls line 5)
# EAN 11521:   ./test.php bm5_30 2420 "7777247853485319" "27427131" "7777247853496092" "35672009" ""
# SCV8 36654:  ./test.php bm5_31 2420 "7777247853582512" "46576979" "7777247853595896" "34566355" ""
# SCV4 36654:  ./test.php bm5_31 2420 "7777247853681294" "6442" "7777247853697099" "0017" ""
# no pin:      ./test.php bm5_31 2420 "7777247853485319" "" "7777247853496092" "" ""
$pstr["bm5"] = "merchant_number=99031209997&MID=99031209997&TID=00000000001&DID=00010780160632865187&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm5_30"] = $pstr["bm5"]."&currency_code=GBP&source_code=30&promo=11521";
$pstr["bm5_31"] = $pstr["bm5"]."&currency_code=GBP&source_code=31&promo=36654";

# Transaction Wireless CAN 124 TPTS (xls line 6)
# EAN 11645:   ./test.php bm6_30 2420 "7777247853782156" "99571347" "7777247853797126" "60267291" ""
# SCV8 45651:  ./test.php bm6_31 2420 "7777247853887827" "66677786" "7777247853893309" "47119995" ""
# SCV4 45651:  ./test.php bm6_31 2420 "7777247853980245" "6197" "7777247853990590" "5434" ""
# no pin:      ./test.php bm6_31 2420 "7777247853782156" "" "7777247853797126" "" ""
$pstr["bm6"] = "merchant_number=99031239997&MID=99031239997&TID=00000000001&DID=00010780152815302427&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm6_30"] = $pstr["bm6"]."&currency_code=CAN&source_code=30&promo=11645";
$pstr["bm6_31"] = $pstr["bm6"]."&currency_code=CAN&source_code=31&promo=45651";

# Transaction Wireless CAN FT12 (xls line 7)
# EAN 11645:   ./test.php bm7_30 2420 "7777247853782156" "99571347" "7777247853797126" "60267291" ""
# SCV8 45651:  ./test.php bm7_31 2420 "7777247853887827" "66677786" "7777247853893309" "47119995" ""
# SCV4 45651:  ./test.php bm7_31 2420 "7777247853980245" "6197" "7777247853990590" "5434" ""
# no pin:      ./test.php bm7_31 2420 "7777247853782156" "" "7777247853797126" "" ""
$pstr["bm7"] = "merchant_number=99023879997&MID=99023879997&TID=00000000000&DID=00035601473336853081&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["bm7_30"] = $pstr["bm7"]."&currency_code=CAN&source_code=30&promo=11645";
$pstr["bm7_31"] = $pstr["bm7"]."&currency_code=CAN&source_code=31&promo=45651";


# Panera - Transaction Wireless 20PCT B2C (prod, 2 Apr 2020)
$pstr["prod_panera_tw_20pct_b2c"] = "merchant_number=99023559997&MID=99023559997&TID=00000900270&DID=00119464825866678307&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera - Transaction Wireless 20PCT B2B (prod, 2 Apr 2020)
$pstr["prod_panera_tw_20pct_b2b"] = "merchant_number=99023559996&MID=99023559996&TID=00000900260&DID=00119464840616447153&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera -  Transaction Wireless 20PCT B2B ACH (prod, 2 Apr 2020)
$pstr["prod_panera_tw_20pct_b2b_ach"] = "merchant_number=99023559995&MID=99023559995&TID=00000900261&DID=00119464882707086692&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera - Transaction Wireless 20PCT B2C (test, 27 Mar 2020)
$pstr["test_panera_tw_20pct_b2c"] = "merchant_number=99023559997&MID=99023559997&TID=00000000001&DID=00037424559033408779&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera - Transaction Wireless 20PCT B2B (test, 27 Mar 2020)
$pstr["test_panera_tw_20pct_b2b"] = "merchant_number=99023559996&MID=99023559996&TID=00000000001&DID=00037424566474097275&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera -  Transaction Wireless 20PCT B2B ACH (test, 27 Mar 2020)
$pstr["test_panera_tw_20pct_b2b_ach"] = "merchant_number=99023559995&MID=99023559995&TID=00000000001&DID=00037424574778734934&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera - TW B2B 20% - With the 5.25% MOR fee the total discount = .2525 (test, 19 Mar 2020 - wrong, withdrawn)
#$pstr["test_panera_tw_b2b_20pct"] = "merchant_number=97555600903&MID=97555600903&TID=00000000000&DID=?&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Petsmart US B2B GYFT (prod, 6 Nov 2019):
$pstr["prod_petsmart_us_b2b_gyft"] = "merchant_number=99025839996&MID=99025839996&TID=00000000000&DID=00110592386583792825&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Petsmart US B2B (prod, 6 Nov 2019):
$pstr["prod_petsmart_us_b2b"] = "merchant_number=99025839997&MID=99025839997&TID=00000000000&DID=00110592486035415210&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Petsmart CA B2B (prod, 6 Nov 2019):
$pstr["prod_petsmart_ca_b2b"] = "merchant_number=99025829997&MID=99025829997&TID=00000000000&DID=00110592521568489300&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=124";

# Winco (prod, 16 Sep 2019)
$pstr["prod_winco"] = "merchant_number=99023969997&MID=99023969997&TID=99023969997&DID=00108159572630601658&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# TW CA Web MID - FT12 CAD (prod, 3 Sep 2019)
$pstr["prod_tw_ca_web"] = "merchant_number=99023879997&MID=99023879997&TID=00000000001&DID=00107149922906338286&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=124";

# TW CA Web MID - FT12 CAD (test, 21 Aug 2019)
$pstr["test_tw_ca_web"] = "merchant_number=99023879997&MID=99023879997&TID=00000000000&DID=00035601473336853081&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=124";

# Petsmart (test, 13 Aug 2019)
$pstr["test_petsmart_us_b2b_gyft"] = "merchant_number=99025839996&MID=99025839996&TID=00000000001&DID=00035512123495272317&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=44726";

# Petsmart (test, 18 Jul 2019)
$pstr["test_petsmart_us_b2b"] = "merchant_number=99025839997&MID=99025839997&TID=00000000001&DID=00035088920097146751&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["test_petsmart_ca_b2b"] = "merchant_number=99025829997&MID=99025829997&TID=00000000001&DID=00035088931454317441&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Ace (prod, 17 Jul 2019)
$pstr["prod_ace"] = "merchant_number=99024129997&MID=99024129997&TID=00000000001&DID=00104195612776112773&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Winco (test, 12 Jul 2019)
$pstr["test_winco"] = "merchant_number=99023969997&MID=99023969997&TID=00000000001&DID=00035072536889063434&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Ace (test, 25 Jun 2019)
$pstr["test_ace"] = "merchant_number=99024129997&MID=99024129997&TID=00000000001&DID=00034994815858027261&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

# Panera - Corporate Discounts (prod, 18 Apr 2019)
$pstr["prod_panera_99024319997"] = "merchant_number=99024319997&MID=99024319997&TID=00000900100&DID=00099602006361588749&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=900100";

# Panera - Together We're Better (prod, 18 Apr 2019)
$pstr["prod_panera_99024319993"] = "merchant_number=99024319993&MID=99024319993&TID=00000900250&DID=00099602014292812696&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=900250";

# Panera - Corporate Discounts (test, 4 Apr 2019)
$pstr["test_panera_99024319997"] = "merchant_number=99024319997&MID=99024319997&TID=00000000000&DID=00034538767848235702&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=0001";

# Panera - Together We're Better (test, 4 Apr 2019)
$pstr["test_panera_99024319993"] = "merchant_number=99024319993&MID=99024319993&TID=00000000000&DID=00034538772501006064&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=0001";

// Panera production, 12 Mar 2019
$pstr["prod_panera_99024239996"] = "merchant_number=99024239996&MID=99024239996&TID=00000900140&DID=00096954325638820246&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=900140";

// Panera test, 11 Mar 2019
$pstr["test_panera_99024239996"] = "merchant_number=99024239996&MID=99024239996&TID=00000000000&DID=00034393428823561984&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=0001";

// Panera production, 5 Mar 2019
// Transaction Wireless B2C
$pstr["prod_panera_99024239997"] = "merchant_number=99024239997&MID=99024239997&TID=00000900270&DID=00096442441988624495&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
// Transaction Wireless B2B
$pstr["prod_panera_99024219996"] = "merchant_number=99024219996&MID=99024219996&TID=00000900260&DID=00096442463825909732&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
// Transaction Wireless Guest Relations / Marketing
$pstr["prod_panera_99024229994"] = "merchant_number=99024229994&MID=99024229994&TID=00000909280&DID=00096442472086524360&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
// Transaction Wireless B2B ACH
$pstr["prod_panera_99024219995"] = "merchant_number=99024219995&MID=99024219995&TID=00000900261&DID=00096442494685934535&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

// Panera testing, 22 Feb 2019
$pstr["panera_99024219995_0"] = "merchant_number=99024219995&MID=99024219995&TID=00000000000&DID=00034324596502596202&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["panera_99024219995_1"] = "merchant_number=99024219995&MID=99024219995&TID=00000000001&DID=00034324607626974133&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

// BWW CA, 19 Feb 2019
$pstr["bww_ean"] = "merchant_number=99032809997&DID=00017313520045460248&MID=99032809997&TID=00000000000&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&merchant_number=99031239997&source_code=30&currency_code=CAD";
$pstr["bww_no_ean"] = "merchant_number=99032809997&DID=00017313520045460248&MID=99032809997&TID=00000000000&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&merchant_number=99031239997&source_code=31&currency_code=CAD";

// Panera testing, 8 Feb 2019
$pstr["panera_b2c"] = "merchant_number=99024239997&MID=99024239997&TID=00000000000&DID=00033999034160082949&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["panera_b2b"] = "merchant_number=99024219996&MID=99024219996&TID=00000000000&DID=00033999044844317143&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["panera_guest_relations"] = "merchant_number=99024229994&MID=99024229994&TID=00000000000&DID=00033999050741486485&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

// Panera testing, 22 Jan 2019
$pstr["panera1"] = "merchant_number=99024909995&MID=99024909995&TID=00000000000&DID=00033884961301919512&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["panera2"] = "merchant_number=99024909996&MID=99024909996&TID=00000000000&DID=00033884958141328132&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

// Sonic testing, 17 Dec 2018
$pstr["sonic"] = "merchant_number=99032809997&DID=00010743194075661445&MID=99032809997&TID=00000000000&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=31";

// set GBP MWK (production) - ./test.php gbp 2010 1
$pstr["gbp"] = "currency_code=826&merchant_number=99031209997&DID=00017313520045460248&MID=99032809997&TID=00000000000&SVCID=104";

// test 2101 (staging)
$pstr["test_2101"] = "activation_code=2101&merchant_number=99024979995&MID=99024979995&TID=00000009999&DID=00032367572610697944&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=34530";

// Neiman Marcus (production)
$pstr["nm_production"] = "merchant_number=99024979995&MID=99024979995&TID=00000000003&DID=00085125660338725234&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
// Neiman Marcus (staging)
$pstr["nm"] = "merchant_number=99024979995&MID=99024979995&TID=00000009999&DID=00032367572610697944&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=34530";

// Del Taco
$pstr["deltaco_production"] = "merchant_number=99024909997&MID=99024909997&TID=00000009999&DID=00083597956343325392&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=34421&source_code=30&alternate_merchant_number=9999";
$pstr["deltaco_testconv"] = "merchant_number=99032809997&MID=99032809997&TID=00000000000&DID=00010743194075661445&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=34421&source_code=30&alternate_merchant_number=0000";
$pstr["deltaco_testweb"] = "merchant_number=99024909997&MID=99024909997&TID=00000000000&DID=00032129590277131005&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&promotion_code=34421&source_code=30&alternate_merchant_number=9999&auto_detect_pin=0";

// Starbucks Malaysia
// 2010 - Assign Merchant Working Key
// 2102 - Activate Virtual Card
// 2104 - Activate Physical Card
// 2600 - Cashout
// 2802 - Void of Activation
// 0704 - TimeOut Reversal
// 2400 - Balance Inquiry
// 2411 - Most Recent Transaction History
// 2300 - Reload
// 2801 - Void of Reload
// virt, ean or noean, error 09

//'7777119535955661' '71640443'
//'7777119535968291' '50950441'
//'7777119535976061' ''
//'7777119535980293' ''

// phy, ean4 + noean, all good
// phy, ean8 + noean, all good
$pstr["myr"] = "merchant_number=99025129997&MID=99025129997&TID=00000000001&DID=00031080608074285898&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=458&source_code=30&promotion_code=29301";
$pstr["myr_noean"] = "merchant_number=99025129997&MID=99025129997&TID=00000000001&DID=00031080608074285898&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=458&source_code=31&promotion_code=29301";
$pstr["myr_prod"] = "merchant_number=99025129997&MID=99025129997&TID=00000000001&DID=00084343950898521217&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=458&source_code=30&promotion_code=XXXXX";

// production, test balance check
$pstr["agg16_free"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=31&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1";

// Supervalu set MWK, 27 Oct 2017
$pstr["sv2010_test_0"] = "merchant_number=97223302738&MID=97223302738&TID=00000002801&DID=00028279346010942902&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";
$pstr["sv2010_prod_0"] = "merchant_number=97223302738&MID=97223302738&TID=00000002801&DID=00072669656304616738&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30";

// Date 11_21_2024 Fanatics (Request https://enterprise-jira.onefiserv.net/browse/GGS-8017)
$pstr["fanatics_eu_production"] = "merchant_number=99031229997&MID=99031229997&TID=00000000001&DID=00255731298987935119&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=978&source_code=30&promotion_code=84962";

// Supervalu registration
$pstr["supervalu_2801"] = "merchant_number=97223302738&MID=97223302738&TID=00000002801&DID=00028279346010942902&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=30&promotion_code=XXXXXX";

// 2403 testing as per Paul.Dattilo@firstdata.com email 3 Oct 2017
$pstr["pd2403"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=31&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1";

// "Testing new TW capabilities" (on production) - as per Paul.Dattilo@firstdata.com email 19 Sep 2017
$pstr["pdtest1"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00040804064439123324&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=1886160";
$pstr["pdtest1ab"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=30&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1";
$pstr["pdtest1abx1"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=30&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=123,9306";
$pstr["pdtest1abx2"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=30&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=123,456";
$pstr["pdtest1abx3"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=30&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=123";
$pstr["pdtest1cd"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00040804064439123324&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=1886160";
$pstr["pdtest2"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00040804064439123324&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=31&promotion_code=1886160";
$pstr["pdtest2ab"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&source_code=31&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=123,9306";
$pstr["pdtest4"] = "merchant_number=99032809997&MID=99032809997&TID=00000000000&DID=00017313520045460248&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=31";

// Petsmart US/CAN, 21 July 2017
$pstr["petsmart_us"] = "merchant_number=99025789997&MID=99025789997&TID=00000000001&DID=00026449287603357721&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";
$pstr["petsmart_can"] = "merchant_number=99025779997&MID=99025779997&TID=00000000001&DID=00026449275271599652&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7";

// Bed Bath Beyond, 30 May 2017 - 16 June 2017
$pstr["bbb_production"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00040804064439123324&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=277272";
// Con Promo Code
// 8600 33683 - Buy Buy Baby GYFT
// 8599 33684 - Harmon Face Values GYFT
// 9336 33685 - Cost Plus GYFT
// 9431 33686 - One Kings Lane GYFT
// 5150 33689 - Christmas Tree Shops GYFT
$pstr["bbb_33683"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00011548336993355311&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=33683&client_id=8600";
$pstr["bbb_33684"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00011548336993355311&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=33684&client_id=8599";
$pstr["bbb_33685"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00011548336993355311&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=33685&client_id=9336";
$pstr["bbb_33686"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00011548336993355311&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=33686&client_id=9431";
$pstr["bbb_33689"] = "merchant_number=99028259997&MID=99028259997&TID=00000000001&DID=00011548336993355311&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&source_code=30&promotion_code=33689&client_id=5150";

// Giftango
$pstr["giftango1"] = "merchant_number=99032569997&MID=99032569997&TID=00000000001&DID=DID=00018834993501875069&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&source_code=30";

// Agg16 Billed testing, 10 Mar 2017 - 3 Apr 2017
// EAN_Gyft_25Denom_Active_USD_p29602_m38951.txt
// transaction list: 2400 2495 2496
// card: 7777091190917942 92298699
$pstr["agg16_billed"] = "merchant_number=99028259996&MID=99028259996&TID=00000000001&DID=00016392506562832470&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&promotion_code=29602&source_code=30";

// MXN testing, 10 Mar 2017
// 4-digit EAN
$pstr["mxn_28863"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=28863&source_code=30";
// 8-digit EAN
$pstr["mxn_28864"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=28864&source_code=30";
// no EAN
$pstr["mxn_28864_noean"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=28864&source_code=31";

// MXN certification testing, 22 Feb 2017
// EAN_BestBuy_Mex_TW_nDenom_p26925_m39974.txt
$pstr["mxn_26925"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=26925&source_code=30";
// EAN_BestBuy_Mex_TW_nDenom_Virt_p30686_m39975.txt
$pstr["mxn_30686"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=30686&source_code=30";
// alternate promo provided by Astra Fearon via email
$pstr["mxn_32763"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00016392511488373206&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=32763&source_code=30";
// production, used to set mwk
$pstr["mxn_32763_production"] = "merchant_number=99026049997&MID=99026049997&TID=00000000001&DID=00062647399756696354&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&currency_code=484&alternate_merchant_number=1&promotion_code=32763&source_code=30";

// Agg16 troubleshoot provisioning, Olive Garden cardtype_id=1924, 15 Nov 2016
$pstr["agg16_1924"] = "SVCID=104&merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&source_code=31&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=8461";

// Agg16 troubleshoot provisioning, Red Robin cardtype_id=1984, 15 Nov 2016
$pstr["agg16_1984"] = "SVCID=104&merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&source_code=31&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&client_id=8571,301,8678,9194,8887,8998,9198,9388";

// Agg17 troubleshoot activation, 10 Nov 2016
$pstr["agg17_1985"] = "merchant_number=97483900003&MID=97483900003&TID=00000000001&DID=00041427269284606752&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&source_code=31&alternate_merchant_number=1";

// Agg16 troubleshoot provisioning, 10 Nov 2016
$pstr["agg16_1584"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&alternate_merchant_number=1&client_id=288,8543,8670,8681&source_code=30&terminal_id=1";

// Agg16 Red Robin lengthy consortium/client_id list testing (in production)
$pstr["redrobin"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00029592790240118904&SVCID=104&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&source_code=31&client_id=301,8678,8571,9194,8887,9198,9388,8998";

// Agg16 testing Aug 2016 (activateCard using Agg17 params, source_code must match)
$pstr["agg16"] = "merchant_number=99029589997&MID=99029589997&TID=00000000001&DID=00010897973284911842&SVCID=104&alternate_merchant_number=1&mwkid=9999&mwk=9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846&terminal_id=1&source_code=30&client_id=1014";

// Agg17 testing May 2016
// promo    client_id
// 31027    1014       TW - EAN NonDenom
$pstr["31027"] = "merchant_number=97483900003&MID=97483900003&TID=00000000001&DID=00012441893877421975&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&client_id=1014&promotion_code=31027&source_code=30";
// 31028    1014       TW - SCV NonDenom
$pstr["31028"] = "merchant_number=97483900003&MID=97483900003&TID=00000000001&DID=00012441893877421975&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&client_id=1014&promotion_code=31028&source_code=31";
// 31029    1014       TW - Pinless NonDenom
$pstr["31029"] = "merchant_number=97483900003&MID=97483900003&TID=00000000001&DID=00012441893877421975&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&client_id=1014&promotion_code=31029&source_code=31";
// 31030    1014       TW - EAN 4-digit NonDenom
$pstr["31030"] = "merchant_number=97483900003&MID=97483900003&TID=00000000001&DID=00012441893877421975&SVCID=104&mwk=0838FBE34F37737A894F2376250E2C100838FBE34F37737A&mwkid=7&alternate_merchant_number=1&client_id=1014&promotion_code=31030&source_code=30";

//$CLUSTER = "test";
$CLUSTER = trim(file_get_contents("/var/tw/cluster"));
//$SRCDN = "../../..";
//$SRCDN = "../tw";
$SRCDN = "/root/tw";
$c = file_get_contents($SRCDN."/giftcard/cluster_config/config_".$CLUSTER.".php");
eval($c);

function logInfo($msg) {$msg = str_replace(array("\r\n","\r","\n"), " ", $msg); echo("logInfo: $msg\n");}
function logError($msg) {$msg = str_replace(array("\r\n","\r","\n"), " ", $msg); echo("logError: $msg\n");}

class Load { function model() {} function helper() {} }
class Benchmark { function mark() {} }
class Model extends Load { var $load; var $benchmark; function Model() {$this->load = new Load; $this->benchmark = new Benchmark;} }
class CI_Model extends Load { var $load; var $benchmark; function CI_Model() {$this->load = new Load; $this->benchmark = new Benchmark;} }

define("BASEPATH","$SRCDN");
include $SRCDN."/giftcard/source/wgiftcard/application/helpers/numbers_helper.php";

//include "svp_params.php";
include $SRCDN."/giftcard/source/wgiftcard/application/models/svp_model.php";
$svp = new Svp_model;
if (is_file("firstdata_model.php")) {
        include "firstdata_model.php";
} else {
        include $SRCDN."/giftcard/source/wgiftcard/application/models/firstdata_model.php";
}
if (is_file("firstdata_conversion_model.php")) {
        include "firstdata_conversion_model.php";
} else {
//      include $SRCDN."/giftcard/source/wgiftcard/application/models/firstdata_conversion_model.php";
}
if (1) {
        $obj = new Firstdata_model;
} else {
        $obj = new Firstdata_conversion_model;
}
$card = array();

function parse_parameters($pstr) {
        $parameters = array();
        foreach (explode("&", $pstr) as $varval) {
                $a = explode("=", $varval);
                $parameters[$a[0]] = $a[1];
        }
        return $parameters;
}

$argv0 = $argv[0];
if ($argc < 2) {
        echo("Usage: $argv0 <parameter index> <command> [<args>]\n");
        echo("Where <command> is one of:\n");
        echo("noop\n");
        echo("7400\n");
        echo("activateCard <amt>\n");
        echo("2801 <account_number> <reference_number> <amt> (voidReload)\n");
        echo("2802 <account_number> <reference_number> <amt> (voidActivation)\n");
        echo("voidCard <account_number> <reference_number> <amt>\n");
        echo("2400 <account_number> <reference_number> (getBalance)\n");
        echo("getHistory <account_number> <reference_number>\n");
        echo("2800 <account_number> <reference_number> <amt> (voidRedemption)\n");
        echo("2101 <amt>\n");
        echo("2107 <amt>\n");
        echo("2108 <amt>\n");
        echo("2301 <amt>\n");
        echo("2495 <account_number> <reference_number> (provisionCard)\n");
        echo("2496 <account_number> <reference_number> (unprovisionCard)\n");
        echo("2202 <account_number> <reference_number> <amt>\n");
        echo("2408 <account_number> <reference_number> <amt>\n");
        echo("2208 <account_number> <reference_number> <amt> <lockid>\n");
        echo("2808 <account_number> <reference_number> <amt> <lockid>\n");
        echo("2403 <account_number> <reference_number>\n");
        echo("2420 <source account_number> <source reference_number> <destination account_number> <destination reference_number> <amt>\n");
        echo("2806 <source account_number> <source reference_number> <destination account_number> <destination reference_number> <amt>\n");
        exit(1);
}

$pidx = $argv[1];
array_shift($argv);
if (!isset($pstr[$pidx])) {
        echo("Unknown parameter set.\n");
        exit(1);
}
$parameters = parse_parameters($pstr[$pidx]);
$parameters['logtofile'] = "/tmp/firstdata.log";
$parameters['debug'] = true;
//$parameters['debug'] = false;

print_r($parameters);

function card_str($card) {
        return "'".(isset($card['account_number'])?$card['account_number']:"")."' '".(isset($card['reference_number'])?$card['reference_number']:"")."'";
}

function cmd_echo($str) {
        global $argv0, $pidx;
        echo($argv0." ".$pidx." ".$str."\n");
}

$t0 = microtime(true);

switch ($argv[1]) {
case "noop":
        $result = false;
        break;

case "2102": // _activateVirtualCard
        $card['account_number'] = "";
        $card['reference_number'] = "";
        $amt = $argv[2];
        $result = $obj->activateCard($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        cmd_echo("2802 ".card_str($card)." ".$amt." (voidActivation)");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("getHistory ".card_str($card));
        break;

case "2104": // _activatePhysicalCard
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->activateCard($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        cmd_echo("2802 ".card_str($card)." ".$amt." (voidActivation)");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("getHistory ".card_str($card));
        break;

case "2801":
case "voidReload":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->voidReload($parameters, $card, $amt);
        cmd_echo("getHistory ".card_str($card));
        break;

case "2802":
case "voidActivation":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->voidActivation($parameters, $card, $amt);
        break;

case "voidCard":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->voidCard($parameters, $card, $amt);
        break;

case "2400":
case "getBalance":
        $vdata['recipient']['card']['account_number'] = $argv[2];
        $vdata['recipient']['card']['reference_number'] = $argv[3];
        $result = $obj->getBalance($parameters, $vdata, $amt);
        echo("amt=".print_r($amt,1)."\n");
        break;

case "2300":
case "incBalance":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->incBalance($parameters, $card, $amt);
        echo("amt=".print_r($amt,1)."\n");
        cmd_echo("2801 ".card_str($card)." ".$amt." (voidReload)");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        break;

case "2600":
case "cashoutCard":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $result = $obj->cashoutCard($parameters, $card);
        $amt = 0;
        cmd_echo("2800 ".card_str($card)." ".$amt." (voidRedemption)");
        break;

case "2411":
        $parameters['history_code'] = "2411";
        // fall thru
case "2410":
case "getHistory":
        $vdata['recipient']['card']['account_number'] = $argv[2];
        $vdata['recipient']['card']['reference_number'] = $argv[3];
        $result = $obj->getHistory($parameters, $vdata, $history);
        echo("history=".print_r($history,1)."\n");
        break;

case "2800":
case "voidRedemption":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->voidRedemption($parameters, $card, $amt);
        break;

case "2101": // Activate Virtual Card w/SCV
        $amt = $argv[2];
        $result = $obj->t_2101($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("2802 ".card_str($card)." ".$amt." (voidActivation)");
        break;

case "2107": // SCV Virtual Activation
        $amt = $argv[2];
        $result = $obj->t_2107($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("2802 ".card_str($card)." ".$amt." (voidActivation)");
        cmd_echo("2301 ".card_str($card)." ".$amt);
        cmd_echo("2495 ".card_str($card)." (provisionCard)");
        cmd_echo("2202 ".card_str($card)." ".$amt);
        cmd_echo("2408 ".card_str($card)." ".$amt);
        break;

case "2108": // EAN Virtual Activation
        $amt = $argv[2];
        $result = $obj->t_2108($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("2802 ".card_str($card)." ".$amt." (voidActivation)");
        cmd_echo("2301 ".card_str($card)." ".$amt);
        cmd_echo("2495 ".card_str($card)." (provisionCard)");
        cmd_echo("2202 ".card_str($card)." ".$amt);
        cmd_echo("2408 ".card_str($card)." ".$amt);
        break;

case "2121": // NRTM Card Registration
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $result = $obj->t_2121($parameters, $card);
        cmd_echo("2821 ".card_str($card));
        break;

case "2821": // NRTM Card De-Registration
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $result = $obj->t_2821($parameters, $card);
        break;

case "2301":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->t_2301($parameters, $card, $amt);
        cmd_echo("2400 ".card_str($card)." (getBalance)");
        cmd_echo("2801 ".card_str($card)." ".$amt." (voidReload)");
        break;

case "provisionCard":
case "2495":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $result = $obj->provisionCard($parameters, $card, $track_ii);
        echo("track_ii=".print_r($track_ii,1)."\n");
        cmd_echo("2496 ".card_str($card)." (unprovisionCard)");
        break;

case "unprovisionCard":
case "2496":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $result = $obj->unprovisionCard($parameters, $card);
        break;

case "2202":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $result = $obj->t_2202($parameters, $card, $amt);
        echo("card=".print_r($card,1)."\n");
        echo("amt=".print_r($amt,1)."\n");
        cmd_echo("2800 ".card_str($card)." ".$amt." (voidRedemption)");
        break;

case "2408":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $lockid = "";
        $result = $obj->t_2408($parameters, $card, $amt, $lockid);
        echo("card=".print_r($card,1)."\n");
        echo("lockid=".print_r($lockid,1)."\n");
        cmd_echo("2208 ".card_str($card)." ".$amt." ".$lockid);
        cmd_echo("2808 ".card_str($card)." ".$amt." ".$lockid);
        break;

case "2208":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $lockid = $argv[5];
        $result = $obj->t_2208($parameters, $card, $amt, $lockid);
        echo("card=".print_r($card,1)."\n");
        break;

case "2808":
        $card['account_number'] = $argv[2];
        $card['reference_number'] = $argv[3];
        $amt = $argv[4];
        $lockid = $argv[5];
        $result = $obj->t_2808($parameters, $card, $amt, $lockid);
        echo("card=".print_r($card,1)."\n");
        break;

case "2403":
        $vdata['recipient']['card']['account_number'] = $argv[2];
        $vdata['recipient']['card']['reference_number'] = $argv[3];
        $amt = "nil";
        $cardstatus = "nil";
        $result = $obj->t_2403($parameters, $vdata, $amt, $cardstatus);
        echo("amt=".print_r($amt,1)."\n");
        echo("cardstatus=".print_r($cardstatus,1)."\n");
        break;

case "balanceMerge":
case "2420":
        $src_card['account_number'] = $argv[2];
        $src_card['reference_number'] = $argv[3];
        $dst_card['account_number'] = $argv[4];
        $dst_card['reference_number'] = $argv[5];
        $amt = $argv[6];
        $result = $obj->balanceMerge($parameters, $src_card, $dst_card, $amt);
        cmd_echo("2806 ".card_str($src_card)." ".card_str($dst_card)." ".$amt." (voidBalanceMerge)");
        cmd_echo("2400 ".card_str($src_card)." (getBalance)");
        cmd_echo("2400 ".card_str($dst_card)." (getBalance)");
        break;

case "voidBalanceMerge":
case "2806":
        $src_card['account_number'] = $argv[2];
        $src_card['reference_number'] = $argv[3];
        $dst_card['account_number'] = $argv[4];
        $dst_card['reference_number'] = $argv[5];
        $amt = $argv[6];
        $result = $obj->voidBalanceMerge($parameters, $src_card, $dst_card, $amt);
        cmd_echo("2400 ".card_str($src_card)." (getBalance)");
        cmd_echo("2400 ".card_str($dst_card)." (getBalance)");
        break;

case "2010":
case "workingKey":
        # standard wGiftcard keys
        $MWKID[1] = 7;
        $MWK[1] = "7D70BF8246931B4C27E4FA8BE3AE2911E6298465F71C9F90A488ECC8A4C823AEC91317D87B613D2D";
        $DMWK[1] = "0838FBE34F37737A894F2376250E2C100838FBE34F37737A";
        $MWKID[2] = 8;
        $MWK[2] = "857EB50F442A8336E939D5F38F47E39498B4DCC13B525464804D30A690B9DE9EEC42E0954809D0B7";
        $DMWK[2] = "AD91DA83A2F7133BAEAD3E3E5DD040D6AD91DA83A2F7133B";
        $MWKID[3] = 9;
        $MWK[3] = "29BFF3AA4C69D0D71549CB217C3AFF241D5405E8C2797EA96C4442910CFE4413547057D92A865CF7";
        $DMWK[3] = "AE685BC4EA5EC2DAFB62C740851A46F1AE685BC4EA5EC2DA";
        $MWKID[4] = 9999;
        $MWK[4] = "024EBCB107227C33A054BAD6EBB39CCA06AFC81F8A79831ACC4D27C17F12370E0C4CFACC3819471A";
        $DMWK[4] = "9E38B9E53DE9F8464F49167A57F8E6919E38B9E53DE9F846";

        $x = $argv[2];
        $parameters['source_code'] = "30";
        $parameters['mwkid'] = $MWKID[$x];
        $parameters['mwk'] = $MWK[$x];
        $result = $obj->workingKey($parameters);
        break;

case "7400":
case "status":
        $result = $obj->t_7400($parameters);
        break;

default:
        echo("Unknown command ".$argv[1]."\n");
        exit(1);
}

echo("result=".print_r($result,1)."\n");
$dt = round(1000.0*(microtime(true)-$t0))."ms";
echo($dt."\n");
exit(0);

?>

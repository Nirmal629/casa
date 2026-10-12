-- Repair incomplete local schema imports: both tables require generated primary keys.
ALTER TABLE `to_tournaments`
    MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`ID`);

ALTER TABLE `to_tournamet_banners`
    MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`ID`);

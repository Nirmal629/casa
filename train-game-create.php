<!-----new-game-host------>
<div class="newgame_host">
    <div class="custom_card">
        <h6 class="card_heading">New Traning Game Create</h6>
        <form>
            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="host-name" class="form-label">Host Name<span>*</span></label>
                    <input type="text" class="form-control" id="host-name" placeholder="Enter Full Name">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCountry" class="form-label">Event Country<span>*</span></label>
                    <input type="text" class="form-control" id="eventCountry" placeholder="Event Country">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventProvince" class="form-label">Event Province<span>*</span></label>
                    <input type="text" class="form-control" id="eventProvince" placeholder="Event Province">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCity" class="form-label">Event City<span>*</span></label>
                    <input type="text" class="form-control" id="eventCity" placeholder="Event City">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCurrency" class="form-label">Event Currency<span>*</span></label>
                    <input type="text" class="form-control" id="eventCurrency" placeholder="Event Currency">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCurrency" class="form-label">Event Currency<span>*</span></label>
                    <input type="text" class="form-control" id="eventCurrency" placeholder="Event Currency">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventVenue" class="form-label">Event Venue<span>*</span></label>
                    <input type="text" class="form-control" id="eventVenue" placeholder="Event Venue">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCategory" class="form-label">Event Category<span>*</span></label>
                    <select class="form-select form-control" id="eventCategory" aria-label="">
                        <option selected value="Badminton">Badminton Game</option>
                        <option disabled value="Tennis">Tennis Game</option>
                        <option disabled value="Cricket">Cricket Game</option>
                        <option disabled value="Football">Football Game</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="genderCategory" class="form-label">Gender Category<span>*</span></label>
                    <select class="form-select form-control" id="genderCategory" aria-label="">
                        <option selected value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Mix">Mix</option>
                        <option value="Kids">Kids</option>
                        <option value="Training">Training</option>
                        <option value="Training">Kids + Training</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="genderSkillLevel" class="form-label">Gender Skill Level<span>*</span></label>
                    <select class="form-select form-control" id="genderSkillLevel" aria-label="">
                        <option selected value="Beginner">Beginner</option>
                        <option value="Amateur">Amateur</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advance">Advance</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventType" class="form-label">Event Type<span>*</span></label>
                    <select class="form-select form-control" id="eventType" aria-label="">
                        <option selected value="Public">Public</option>
                        <option value="Invite">Invite Only</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventDate" class="form-label">Event Date<span>*</span></label>
                    <input type="date" class="form-control" id="eventDate" placeholder="Event Date">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventTime" class="form-label">Event Time<span>*</span></label>
                    <input type="time" class="form-control" id="eventTime" placeholder="Event Time">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventCost" class="form-label">Event Cost ($)<span>*</span></label>
                    <input type="text" class="form-control" id="eventCost" placeholder="Event Cost">
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                    <label for="eventDiscount" class="form-label">Event Discount (%)<span>*</span></label>
                    <input type="text" class="form-control" id="eventDiscount" placeholder="Event Discount">
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                    <label for="eventDescription" class="form-label">Event Description<span>*</span></label>
                    <textarea class="form-control" id="eventDescription" rows="1" placeholder="Bring own bat, shoe, guards....."></textarea>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                    <label for="eventMessage" class="form-label">Event Message<span>*</span></label>
                    <textarea class="form-control" id="eventMessage" rows="1" placeholder="For Participants to put note....."></textarea>
                </div>

                <div class="col-auto">
                    <button type="button" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
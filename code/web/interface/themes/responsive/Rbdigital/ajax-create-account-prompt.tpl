{strip}
    <form method="post" action="" id="createRbdigitalAccount" class="form">
        <div>
            Prior to checking out titles and placing holds in Rbdigital, you must create an account.  We've already filled
            out most of the information from your library account.  Simply select a username and password for your account
            and select submit.
        </div>
        <div>
            <input type="hidden" name="id" value="{$id}">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
                <label for="password1">Password</label>
                <input type="password" class="form-control" id="password1" placeholder="Enter password" required>
            </div>
            <div class="form-group">
                <label for="password2">Confirm Password</label>
                <input type="password" class="form-control" id="password2" placeholder="Confirm password" required>
            </div>
            <div class="form-group">
                <label for="libraryCard">Library Card</label>
                <input type="text" class="form-control" id="libraryCard" value="{$user->getBarcode()}" readonly="readonly">
            </div>
            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" class="form-control" id="firstName" value="{$user->firstname}" required>
            </div>
            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" class="form-control" id="lastName" value="{$user->lastname}" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" value="{$user->email}" required>
            </div>
            <div class="form-group">
                <label for="postalCode">Postal Code</label>
                <input type="text" class="form-control" id="postalCode" value="{$user->_zip}" required>
            </div>
        </div>
    </form>
{/strip}
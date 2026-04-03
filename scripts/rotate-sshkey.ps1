<#
.SYNOPSIS
    Rotate an SSH key on a remote server by creating a new key, adding it to the authorized keys, testing it, and then removing the old key.
.PARAMETER remote
    The IP address or hostname of the remote server.
.PARAMETER domain
    The domain name to use in the SSH key comment. If not provided, the remote parameter will be used.
.PARAMETER username
    The username to use for SSH connection.
.PARAMETER keyname
    The base name of the SSH key (without .pub extension).
.EXAMPLE
    .\rotate-sshkey.ps1 -remote '51.75.246.163' -domain 'metanull.eu' -username 'deploy' -keyname 'motivya_deploy'
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string]$remote,
    [Parameter(Mandatory)]
	[string]$domain,
    [Parameter(Mandatory)]
	[string]$username,
    [Parameter(Mandatory)]
	[string]$keyname
)
Begin {
    $ErrorActionPreference = 'Stop'

	Function Get-SshKeyPath {
		param(
			[Parameter(Mandatory)]
			[string] $FileName,
			[switch] $Resolve
		)
		$privkey_path = Join-Path $HOME\.ssh $FileName
		if ($Resolve.IsPresent -and $Resolve -and (-not (Test-Path -Path $privkey_path -PathType Leaf))) {
			throw "private key not found ($FileName)"
		}
		return $privkey_path
	}
	Function Get-PrivateKeyPath {
		param(
			[Parameter(Mandatory)]
			[string] $SshKeyName,
			[switch] $Resolve
		)
		return Get-SshKeyPath -FileName "$($SshKeyName)" -Resolve:$Resolve
	}
	Function Get-PublicKeyPath {
		param(
			[Parameter(Mandatory)]
			[string] $SshKeyName,
			[switch] $Resolve
		)
		return Get-SshKeyPath -FileName "$($SshKeyName).pub" -Resolve:$Resolve
	}
	Function Invoke-Ssh {
		param(
			[Parameter(Mandatory)]
			[string]$UserName,
			[Parameter(Mandatory)]
			[string]$SshKeyName,
			[Parameter(Mandatory)]
			[string]$Remote,
			[Parameter(Mandatory)]
			[string]$Command
			
		)
		$privkey_path = Get-PrivateKeyPath -SshKeyName $SshKeyName
		$SshOutput = ssh -o BatchMode=yes -i "$privkey_path" "$($UserName)@$($Remote)" "$($Command)" 2>&1
		if ($LASTEXITCODE -ne 0) {
			throw "Ssh failed: $SshOutput"
		}
		return $SshOutput
	}
	Function Test-SshCommandOutput {
		param(
			[Parameter(Mandatory)]
			[string]$UserName,
			[Parameter(Mandatory)]
			[string]$SshKeyName,
			[Parameter(Mandatory)]
			[string]$Remote,
			[Parameter(Mandatory)]
			[string]$Command,
			[Parameter(Mandatory)]
			[string]$ExpectedOutput
		)
		$SshOutput = Invoke-Ssh -UserName $UserName -SshKeyName $SshKeyName -Remote $Remote -Command $Command
		if ($SshOutput -ne $ExpectedOutput) {
			throw "Ssh command returned unexpected output`nExpected: $ExpectedOutput`nReceived: $SshOutput"
		}
	}
}
Process {
	
	# Test SSH Connection with the old key
	Test-SshCommandOutput -UserName $username -Remote $remote `
		-SshKeyName $keyname `
		-ExpectedOutput $username `
		-Command 'whoami'

	# Create a new Key
	$newprivkey_path = Get-PrivateKeyPath -SshKeyName "$($keyname)_new"
	ssh-keygen -t ed25519 -C "$($keyname)@$($domain)" -f $newprivkey_path -N ""
	if ($LASTEXITCODE -ne 0) {
		throw "Couldn't generate a new SSH Key."
	}
	
	# Install the new Key in authorized keys of the user
	$newprivkey_path = Get-PrivateKeyPath -SshKeyName "$($keyname)_new" -Resolve
	$newpubkey_path = Get-PublicKeyPath -SshKeyName "$($keyname)_new" -Resolve
	$newpubkey_value = Get-Content -Path $newpubkey_path
	Test-SshCommandOutput -UserName $username -Remote $remote `
		-SshKeyName $keyname `
		-ExpectedOutput 'OK' `
		-Command "echo '$newpubkey_value' >> ~/.ssh/authorized_keys && echo 'OK'" 
	
	# Test the new key can be used
	Test-SshCommandOutput -UserName $username -Remote $remote `
		-SshKeyName "$($keyname)_new" `
		-ExpectedOutput $username `
		-Command 'whoami'
	
	# Remove the old key from authorized keys of the user
	$oldkey_fingerprint = (Get-Content -Path (Get-PublicKeyPath -SshKeyName $keyname -Resolve)) -split ' ',3 | select-object -first 1 -skip 1
	$escaped_fingerprint = $oldkey_fingerprint -replace '[\\.*^$\[\]+]', '\$&' -replace '/', '\\/' 
	Test-SshCommandOutput -UserName $username -Remote $remote `
		-SshKeyName "$($keyname)_new" `
		-ExpectedOutput 'OK' `
		-Command "sed -i '/$($escaped_fingerprint)/d' ~/.ssh/authorized_keys && echo 'OK' || echo 'SED FAILED'"
	
	# Remove the old key
	Rename-Item -Force -Path (Get-PrivateKeyPath -SshKeyName $keyname -Resolve) -NewName "$($keyname)_old" -ErrorAction Stop
	Rename-Item -Force -Path (Get-PublicKeyPath -SshKeyName $keyname -Resolve) -NewName "$($keyname)_old.pub" -ErrorAction Stop
	
	Rename-Item -Force -Path (Get-PrivateKeyPath -SshKeyName "$($keyname)_new" -Resolve) -NewName $keyname -ErrorAction Stop
	Rename-Item -Force -Path (Get-PublicKeyPath -SshKeyName "$($keyname)_new" -Resolve) -NewName "$($keyname).pub" -ErrorAction Stop
}
package com.turning_leaf_technologies.encryption;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.commons.codec.DecoderException;
import org.apache.commons.codec.binary.Hex;

public class EncryptionKey {
	private String cipher;
	private byte[] key;

	public EncryptionKey(String cipher, String key, BaseLogEntry logEntry){
		if (cipher.equals("aes-256-gcm")){
			cipher = "AES_256";
		}else{
			cipher = "AES_128";
		}
		this.cipher = cipher;
		try {
			this.key = Hex.decodeHex(key.toCharArray());
		} catch (DecoderException e) {
			logEntry.incErrors("Could not decrypt encryption key", e);
		}
	}

	public String getCipher(){
		return cipher;
	}

	public byte[] getKey(){
		return key;
	}
}

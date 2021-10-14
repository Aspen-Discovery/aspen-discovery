package com.turning_leaf_technologies.encryption;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.commons.lang3.ArrayUtils;

import javax.crypto.Cipher;
import javax.crypto.CipherInputStream;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.spec.GCMParameterSpec;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.io.*;
import java.security.*;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Base64;
import java.util.HashMap;

public class EncryptionUtils {
	private static HashMap<String, EncryptionKey> encryptionKey = new HashMap<>();
	private static EncryptionKey loadKey(String serverName, BaseLogEntry logEntry){
		if (!encryptionKey.containsKey(serverName)){
			File passKeyFile = new File("../../sites/" + serverName + "/conf/passkey");
			if (passKeyFile.exists()){
				try {
					BufferedReader reader = new BufferedReader(new FileReader(passKeyFile));
					String key = reader.readLine().trim();
					String[] keyParts = key.split(":");
					encryptionKey.put(serverName, new EncryptionKey(keyParts[0].trim(), keyParts[1].trim(), logEntry));
					reader.close();
				} catch (IOException e) {
					logEntry.incErrors("Could not read encryption key", e);
					encryptionKey.put(serverName, null);
				}
			}else {
				encryptionKey.put(serverName, null);
			}
		}
		return encryptionKey.get(serverName);
	}

	static final int GCM_TAG_LENGTH = 16;
	public static String decryptString(String stringToDecrypt, String serverName, BaseLogEntry logEntry) throws Exception {
		EncryptionKey key = loadKey(serverName, logEntry);
		if (key == null){
			return stringToDecrypt;
		}else{
			if (stringToDecrypt != null && stringToDecrypt.length() > 4 && stringToDecrypt.startsWith("AEF~")){
				InputStream cipherInputStream = null;
				Exception decryptionException;
				try {
					Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
					byte[] decodedData = Base64.getDecoder().decode(stringToDecrypt.substring(4));
					int initializationVectorLength = 12;
					byte[] initializationVector = Arrays.copyOfRange(decodedData, 0, initializationVectorLength);
					byte[] tag = Arrays.copyOfRange(decodedData, initializationVectorLength, initializationVectorLength + 16);
					byte[] hmac = Arrays.copyOfRange(decodedData, initializationVectorLength + 16, initializationVectorLength + 16 + 32);
					byte[] encodedText = Arrays.copyOfRange(decodedData, initializationVectorLength + 16 + 32, decodedData.length);

					SecretKeySpec secretKey = new SecretKeySpec(key.getKey(), "AES");
					GCMParameterSpec params = new GCMParameterSpec(GCM_TAG_LENGTH * 8, initializationVector);
					cipher.init(Cipher.DECRYPT_MODE, secretKey, params);
					byte[] decryptedData = cipher.doFinal(ArrayUtils.addAll(encodedText, tag));
					String decryptedString = new String(decryptedData, "UTF-8");
					return decryptedString;
				} catch (Exception e) {
					//logEntry.addNote("Could not decrypt text " + e.toString());
					decryptionException = e;
				} finally {
					if (cipherInputStream != null) {
						try {
							cipherInputStream.close();
						} catch (IOException e) {
							logEntry.incErrors("Could not decrypt text", e);
						}
					}
				}
				if (decryptionException != null){
					throw decryptionException;
				}
				return stringToDecrypt;
			}else{
				return stringToDecrypt;
			}
		}
	}
}

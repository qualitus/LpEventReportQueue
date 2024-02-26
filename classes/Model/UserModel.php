<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace QU\LERQ\Model;

class UserModel
{
    private ?int $usr_id = null;
    private ?string $login = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $title = null;
    private ?string $gender = null;
    private ?string $email = null;
    private ?string $institution = null;
    private ?string $street = null;
    private ?string $city = null;
    private ?string $country = null;
    private ?string $phone_office = null;
    private ?string $hobby = null;
    private ?string $department = null;
    private ?string $phone_home = null;
    private ?string $phone_mobile = null;
    private ?string $fax = null;
    private ?string $referral_comment = null;
    private ?string $matriculation = null;
    private ?int $active = null;
    private ?string $approval_date = null;
    private ?string $agree_date = null;
    private ?string $auth_mode = null;
    private ?string $ext_account = null;
    private ?string $birthday = null;
    private ?array $udf_data = null;
    private ?string $import_id = null;

    public function getUsrId(): int
    {
        return $this->usr_id ?? -1;
    }

    /**
     * @param int $usr_id
     */
    public function setUsrId($usr_id): self
    {
        $this->usr_id = $usr_id;
        return $this;
    }

    public function getLogin(): string
    {
        return $this->login ?? '';
    }

    /**
     * @param string $login
     */
    public function setLogin($login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname ?? '';
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname ?? '';
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * @param string $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getGender(): string
    {
        return $this->gender ?? '';
    }

    /**
     * @param string $gender
     */
    public function setGender($gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email ?? '';
    }

    /**
     * @param string $email
     */
    public function setEmail($email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getInstitution(): string
    {
        return $this->institution ?? '';
    }

    /**
     * @param string $institution
     */
    public function setInstitution($institution): self
    {
        $this->institution = $institution;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street ?? '';
    }

    /**
     * @param string $street
     */
    public function setStreet($street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city ?? '';
    }

    /**
     * @param string $city
     */
    public function setCity($city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country ?? '';
    }

    /**
     * @param string $country
     */
    public function setCountry($country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getPhoneOffice(): string
    {
        return $this->phone_office ?? '';
    }

    /**
     * @param string $phone_office
     */
    public function setPhoneOffice($phone_office): self
    {
        $this->phone_office = $phone_office;
        return $this;
    }

    public function getHobby(): string
    {
        return $this->hobby ?? '';
    }

    /**
     * @param string $hobby
     */
    public function setHobby($hobby): self
    {
        $this->hobby = $hobby;
        return $this;
    }

    public function getDepartment(): string
    {
        return $this->department ?? '';
    }

    /**
     * @param string $department
     */
    public function setDepartment($department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getPhoneHome(): string
    {
        return $this->phone_home ?? '';
    }

    /**
     * @param string $phone_home
     */
    public function setPhoneHome($phone_home): self
    {
        $this->phone_home = $phone_home;
        return $this;
    }

    public function getPhoneMobile(): string
    {
        return $this->phone_mobile ?? '';
    }

    /**
     * @param string $phone_mobile
     */
    public function setPhoneMobile($phone_mobile): self
    {
        $this->phone_mobile = $phone_mobile;
        return $this;
    }

    public function getFax(): string
    {
        return $this->fax ?? '';
    }

    /**
     * @param string $fax
     */
    public function setFax($fax): self
    {
        $this->fax = $fax;
        return $this;
    }

    public function getReferralComment(): string
    {
        return $this->referral_comment ?? '';
    }

    /**
     * @param string $referral_comment
     */
    public function setReferralComment($referral_comment): self
    {
        $this->referral_comment = $referral_comment;
        return $this;
    }

    public function getMatriculation(): string
    {
        return $this->matriculation ?? '';
    }

    /**
     * @param string $matriculation
     */
    public function setMatriculation($matriculation): self
    {
        $this->matriculation = $matriculation;
        return $this;
    }

    public function getActive(): int
    {
        return $this->active ?? -1;
    }

    /**
     * @param int $active
     */
    public function setActive($active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getApprovalDate(): string
    {
        return $this->approval_date ?? '';
    }

    /**
     * @param string $approval_date
     */
    public function setApprovalDate($approval_date): self
    {
        $this->approval_date = $approval_date;
        return $this;
    }

    public function getAgreeDate(): string
    {
        return $this->agree_date ?? '';
    }

    /**
     * @param string $agree_date
     */
    public function setAgreeDate($agree_date): self
    {
        $this->agree_date = $agree_date;
        return $this;
    }

    public function getAuthMode(): string
    {
        return $this->auth_mode ?? '';
    }

    /**
     * @param string $auth_mode
     */
    public function setAuthMode($auth_mode): self
    {
        $this->auth_mode = $auth_mode;
        return $this;
    }

    public function getExtAccount(): string
    {
        return $this->ext_account ?? '';
    }

    /**
     * @param string $ext_account
     */
    public function setExtAccount($ext_account): self
    {
        $this->ext_account = $ext_account;
        return $this;
    }

    public function getBirthday(): string
    {
        return $this->birthday ?? '';
    }

    /**
     * @param string $birthday
     */
    public function setBirthday($birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getImportId(): string
    {
        return $this->import_id ?? '';
    }

    /**
     * @param string $import_id
     */
    public function setImportId($import_id): self
    {
        $this->import_id = $import_id;
        return $this;
    }

    /**
     * @return array
     */
    public function getUdfData(): array
    {
        return $this->udf_data ?? [];
    }

    /**
     * @param array $udf_data
     */
    public function setUdfData($udf_data): self
    {
        $this->udf_data = $udf_data;
        return $this;
    }

    public function __toString(): string
    {
        return json_encode([
            'usr_id' => $this->getUsrId(),
            'username' => $this->getLogin(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'title' => $this->getTitle(),
            'gender' => $this->getGender(),
            'email' => $this->getEmail(),
            'institution' => $this->getInstitution(),
            'street' => $this->getStreet(),
            'city' => $this->getCity(),
            'country' => $this->getCountry(),
            'phone_office' => $this->getPhoneOffice(),
            'hobby' => $this->getHobby(),
            'department' => $this->getDepartment(),
            'phone_home' => $this->getPhoneHome(),
            'phone_mobile' => $this->getPhoneMobile(),
            'phone_fax' => $this->getFax(),
            'referral_comment' => $this->getReferralComment(),
            'matriculation' => $this->getMatriculation(),
            'active' => $this->getActive() == 1,
            'approval_date' => $this->getApprovalDate(),
            'agree_date' => $this->getAgreeDate(),
            'auth_mode' => $this->getAuthMode(),
            'ext_account' => $this->getExtAccount(),
            'birthday' => $this->getBirthday(),
            'import_id' => $this->getImportId(),
            'udf_data' => $this->getUdfData(),
        ], JSON_THROW_ON_ERROR);
    }
}

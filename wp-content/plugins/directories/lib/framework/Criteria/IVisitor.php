<?php
namespace SabaiApps\Framework\Criteria;

interface IVisitor
{
    public function visitCriteriaComposite(CompositeCriteria $criteria, &$valuePassed);
    public function visitCriteriaCompositeNot(CompositeNotCriteria $criteria, &$valuePassed);
    public function visitCriteriaEmpty(EmptyCriteria $criteria, &$valuePassed);
    public function visitCriteriaContains(ContainsCriteria $criteria, &$valuePassed);
    public function visitCriteriaNotContains(ContainsCriteria $criteria, &$valuePassed);
    public function visitCriteriaStartsWith(StartsWithCriteria $criteria, &$valuePassed);
    public function visitCriteriaEndsWith(EndsWithCriteria $criteria, &$valuePassed);
    public function visitCriteriaIn(InCriteria $criteria, &$valuePassed);
    public function visitCriteriaNotIn(NotInCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsNull(IsNullCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsNotNull(IsNotNullCriteria $criteria, &$valuePassed);
    public function visitCriteriaIs(IsCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsNot(IsNotCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsSmallerThan(IsSmallerThanCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsGreaterThan(IsGreaterThanCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsOrSmallerThan(IsOrSmallerThanCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsOrGreaterThan(IsOrGreaterThanCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsField(IsFieldCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsNotField(IsNotFieldCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsSmallerThanField(IsSmallerThanFieldCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsGreaterThanField(IsGreaterThanFieldCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsOrSmallerThanField(IsOrSmallerThanFieldCriteria $criteria, &$valuePassed);
    public function visitCriteriaIsOrGreaterThanField(IsOrGreaterThanFieldCriteria $criteria, &$valuePassed);
}